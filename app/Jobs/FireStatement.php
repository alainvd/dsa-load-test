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


class FireStatement implements ShouldQueue
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
        $url = config('app.remote_url');

        $date_sent = $this->faker->dateTimeThisYear->format('Y-m-d');
        $content_date = $this->faker->dateTimeThisYear->format('Y-m-d');
        $application_date = $this->faker->dateTimeThisYear->format('Y-m-d');


        $data = [
            'start_date' => $date_sent,
            'content_date' => $content_date,
            'application_date' => $application_date,
            'countries_list' => $this->faker->randomElements(["IE","DE","FR","NL","BE"]),
            "decision_visibility" => fake()->randomElements(["DECISION_VISIBILITY_CONTENT_REMOVED","DECISION_VISIBILITY_CONTENT_DISABLED","DECISION_VISIBILITY_CONTENT_DEMOTED"]),
            "decision_monetary" => fake()->randomElement(["DECISION_MONETARY_SUSPENSION","DECISION_MONETARY_TERMINATION"]),
            "decision_provision" => fake()->randomElement(["DECISION_PROVISION_PARTIAL_SUSPENSION","DECISION_PROVISION_TOTAL_SUSPENSION","DECISION_PROVISION_PARTIAL_TERMINATION","DECISION_PROVISION_TOTAL_TERMINATION"]),
            "decision_account" => fake()->randomElement(["DECISION_ACCOUNT_SUSPENDED","DECISION_ACCOUNT_TERMINATED"]),
            "content_type"=> fake()->randomElements(["CONTENT_TYPE_TEXT","CONTENT_TYPE_VIDEO","CONTENT_TYPE_IMAGE"]),
            "category"=> fake()->randomElement(["STATEMENT_CATEGORY_ANIMAL_WELFARE","STATEMENT_CATEGORY_DATA_PROTECTION_AND_PRIVACY_VIOLATIONS","STATEMENT_CATEGORY_ILLEGAL_OR_HARMFUL_SPEECH","STATEMENT_CATEGORY_INTELLECTUAL_PROPERTY_INFRINGEMENTS"]),

            "incompatible_content_illegal"=> fake()->randomElement(["Yes","No"]),
            "decision_facts"=> "facts about the decision",
            "automated_detection"=> fake()->randomElement(["Yes","No"]),
            "automated_decision"=> fake()->randomElement(["AUTOMATED_DECISION_FULLY","AUTOMATED_DECISION_PARTIALLY","AUTOMATED_DECISION_NOT_AUTOMATED"]),
            "source_type"=> fake()->randomElement(["SOURCE_ARTICLE_16","SOURCE_TRUSTED_FLAGGER","SOURCE_VOLUNTARY"]),
            "source" => fake()->word,
            "puid"=> fake()->uuid,
            "url" => fake()->url,
        ];

        $data['decision_ground'] = fake()->randomElement(["DECISION_GROUND_ILLEGAL_CONTENT","DECISION_GROUND_INCOMPATIBLE_CONTENT"]);
        if ($data['decision_ground'] == "DECISION_GROUND_ILLEGAL_CONTENT"){
            $data['illegal_content_legal_ground'] = fake()->text;
            $data['illegal_content_explanation'] = fake()->text;
        }
        if ($data['decision_ground'] == "DECISION_GROUND_INCOMPATIBLE_CONTENT"){
            $data['incompatible_content_ground'] = fake()->text;
            $data['incompatible_content_explanation'] = fake()->text;
        }

        // Create an array to store the cloned objects
        $statements = [];

        // Clone the object 100 times and change the 'field1' value in each clone
        for ($i = 0; $i < 100; $i++) {
            $clonedStatement = $data; // Clone the object
            $clonedStatement['puid'] = fake()->uuid; // Modify the 'field1' in the cloned object
            $statements[] = $clonedStatement; // Store the cloned object in the array
        }


        $data = array('statements' => $statements);

        $response = Http::timeout(60)->connectTimeout(60)->withHeaders([
            'Authorization' => 'Bearer '.config('app.remote_token'),
            'accept' => 'application/json',
            'content-type' => 'application/json'
        ])->post($url, $data);

        if ($response->failed()) {
            Log::info('[ERROR] '.$this->id . ': ' . $response);
        };

    }
}
