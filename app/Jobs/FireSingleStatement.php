<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Faker\Generator;
use Illuminate\Container\Container;
use Carbon\Carbon as CarbonTime;
use Illuminate\Support\Facades\Cache;
use App\Models\StatementResponse;
use App\Models\ApiError;

class FireSingleStatement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $id;
    private $faker;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws ConnectionException
     */
    public function handle()
    {
        $this->faker = Container::getInstance()->make(Generator::class);
        $url = config('app.remote_url_single');

        $date_sent = $this->faker->dateTimeThisYear->format('Y-m-d');
        $content_date = $this->faker->dateTimeThisYear->format('Y-m-d');
        $application_date = $this->faker->dateTimeThisYear->format('Y-m-d');

        $statement = [
            'start_date' => $date_sent,
            'content_date' => $content_date,
            'application_date' => $application_date,
            'countries_list' => $this->faker->randomElements(["IE", "DE", "FR", "NL", "BE"]),
            "decision_visibility" => fake()->randomElements([
                "DECISION_VISIBILITY_CONTENT_REMOVED",
                "DECISION_VISIBILITY_CONTENT_DISABLED",
                "DECISION_VISIBILITY_CONTENT_DEMOTED"
            ]),
            "decision_monetary" => fake()->randomElement([
                "DECISION_MONETARY_SUSPENSION",
                "DECISION_MONETARY_TERMINATION"
            ]),
            "decision_provision" => fake()->randomElement([
                "DECISION_PROVISION_PARTIAL_SUSPENSION",
                "DECISION_PROVISION_TOTAL_SUSPENSION",
                "DECISION_PROVISION_PARTIAL_TERMINATION",
                "DECISION_PROVISION_TOTAL_TERMINATION"
            ]),
            "decision_account" => fake()->randomElement(["DECISION_ACCOUNT_SUSPENDED", "DECISION_ACCOUNT_TERMINATED"]),
            "content_type" => fake()->randomElements(["CONTENT_TYPE_TEXT", "CONTENT_TYPE_VIDEO", "CONTENT_TYPE_IMAGE"]),
            "category" => fake()->randomElement([
                "STATEMENT_CATEGORY_ANIMAL_WELFARE",
                "STATEMENT_CATEGORY_DATA_PROTECTION_AND_PRIVACY_VIOLATIONS",
                "STATEMENT_CATEGORY_ILLEGAL_OR_HARMFUL_SPEECH",
                "STATEMENT_CATEGORY_INTELLECTUAL_PROPERTY_INFRINGEMENTS"
            ]),
            "incompatible_content_illegal" => fake()->randomElement(["Yes", "No"]),
            "decision_facts" => "facts about the decision",
            "automated_detection" => fake()->randomElement(["Yes", "No"]),
            "automated_decision" => fake()->randomElement([
                "AUTOMATED_DECISION_FULLY",
                "AUTOMATED_DECISION_PARTIALLY",
                "AUTOMATED_DECISION_NOT_AUTOMATED"
            ]),
            "source_type" => fake()->randomElement(["SOURCE_ARTICLE_16", "SOURCE_TRUSTED_FLAGGER", "SOURCE_VOLUNTARY"]),
            "source" => fake()->word,
            "puid" => fake()->uuid,
            "url" => fake()->url,
        ];

        $statement['decision_ground'] = fake()->randomElement([
            "DECISION_GROUND_ILLEGAL_CONTENT",
            "DECISION_GROUND_INCOMPATIBLE_CONTENT"
        ]);
        if ($statement['decision_ground'] == "DECISION_GROUND_ILLEGAL_CONTENT") {
            $statement['illegal_content_legal_ground'] = fake()->text;
            $statement['illegal_content_explanation'] = fake()->text;
        }
        if ($statement['decision_ground'] == "DECISION_GROUND_INCOMPATIBLE_CONTENT") {
            $statement['incompatible_content_ground'] = fake()->text;
            $statement['incompatible_content_explanation'] = fake()->text;
        }

        // Send a single statement instead of a batch
        //$data = array('statements' => [$statement]);

        $startTime = CarbonTime::now();

        // Record the first statement timestamp if not already set
        if (Cache::get('single_processing_start') === null) {
            Cache::put('single_processing_start', $startTime->toIso8601String(), now()->addHours(1));
            Log::info('[METRICS] First single statement started sending', [
                'timestamp' => $startTime->toIso8601String(),
                'statement_id' => $this->id
            ]);
        }

        try {
            $response = Http::timeout(60)->connectTimeout(60)->withHeaders([
                'Authorization' => 'Bearer ' . config('app.remote_token'),
                'accept' => 'application/json',
                'content-type' => 'application/json'
            ])->post($url, $statement);

            if ($response->successful()) {
                $responseData = $response->json();
                if (isset($responseData['uuid']) && isset($responseData['created_at'])) {
                    StatementResponse::create([
                        'uuid' => $responseData['uuid'],
                        'response_created_at' => Carbon::parse($responseData['created_at']),
                    ]);
                }
            }

            $endTime = CarbonTime::now();

            // Always update the end time as this could be the last statement
            Cache::put('single_processing_end', $endTime->toIso8601String(), now()->addHours(1));

            // Increment the processed count
            $processed = Cache::increment('single_processed_count');
            $total = Cache::get('total_single_count');

            // Always log the current progress for debugging
//        Log::debug("[METRICS] Single statement progress", [
//            'processed' => $processed,
//            'total' => $total,
//            'statement_id' => $this->id
//        ]);

            // If this is the last statement, log the complete metrics
            if ($processed >= $total) {
                $startTimeStr = Cache::get('single_processing_start');
                $endTimeStr = Cache::get('single_processing_end');

                // Make sure we have valid timestamps
                if ($startTimeStr && $endTimeStr) {
                    $startTimeObj = CarbonTime::parse($startTimeStr);
                    $endTimeObj = CarbonTime::parse($endTimeStr);
                    $duration = $endTimeObj->diffInSeconds($startTimeObj);

                    Log::info('[METRICS] All single statements completed', [
                        'timestamp_start' => $startTimeStr,
                        'timestamp_end' => $endTimeStr,
                        'duration_seconds' => $duration,
                        'total_statements' => $total,
                        'processed_statements' => $processed
                    ]);
                } else {
                    Log::warning('[METRICS] Could not generate final single statement metrics - missing timestamps', [
                        'has_start_time' => (bool)$startTimeStr,
                        'has_end_time' => (bool)$endTimeStr,
                        'processed' => $processed,
                        'total' => $total
                    ]);
                }
            }

            if ($response->failed()) {
                Log::info('[ERROR] Single statement API call failed', [
                    'statement_id' => $this->id,
                    'status' => $response->status(),
                    'response_body' => $response->body()
                ]);

                ApiError::create([
                    'statement_response_id' => $this->id,
                    // Or link to a specific statement if an ID is available before this call
                    'url' => $url,
                    'method' => 'POST',
                    'request_payload' => json_encode($statement),
                    'status_code' => $response->status(),
                    'error_message' => 'API request failed for single statement',
                    'response_body' => $response->body(),
                ]);
            }
        } catch (ConnectionException $e) {
            Log::error('[ERROR] Single statement API connection failed', [
                'statement_id' => $this->id,
                'error' => $e->getMessage(),
            ]);

            ApiError::create([
                'url' => $url,
                'method' => 'POST',
                'request_payload' => json_encode($statement),
                'status_code' => null, // No HTTP status code for connection exception
                'error_message' => $e->getMessage(),
                'response_body' => null,
            ]);
            // Optionally rethrow or handle as per application's error handling strategy
            // throw $e;
        }
    }
}
