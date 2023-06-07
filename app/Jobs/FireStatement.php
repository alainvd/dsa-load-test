<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
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
     */
    public function handle()
    {
        $this->faker = Container::getInstance()->make(Generator::class);
        $url = config('app.remote_url');

        $date_sent = Carbon::createMidnightDate($this->faker->dateTimeThisYear())->addHours($this->faker->numberBetween(3,23))->addMinutes($this->faker->numberBetween(0,59))->addSeconds($this->faker->numberBetween(0,59));

        $data = [
            'start_date' => $date_sent->toDateTimeString(),
            'countries_list' => $this->faker->randomElements(["IE","DE","FR","NL","BE"]),
            "decision_visibility" => fake()->randomElement(["CONTENT_OTHER","CONTENT_DEMOTED","CONTENT_DISABLED","CONTENT_REMOVAL"]),
            "decision_monetary" => fake()->randomElement(["MONETARY_SUSPENSION","MONETARY_TERMINATION","MONETARY_OTHER"]),
            "decision_provision" => fake()->randomElement(["PARTIAL_SUSPENSION","TOTAL_SUSPENSION","PARTIAL_TERMINATION","TOTAL_TERMINATION"]),
            "decision_account" => fake()->randomElement(["ACCOUNT_SUSPENDED","ACCOUNT_TERMINATED"]),
            "content_type"=> fake()->randomElement(["TEXT","VIDEO","IMAGE","OTHER"]),
            "category"=> fake()->randomElement(["PIRACY","DISCRIMINATION","COUNTERFEIT","FRAUD","TERRORISM","CHILD_SAFETY","NON_CONSENT","MISINFORMATION","VIOLATION_TOS","UNCATEGORISED"]),

            "incompatible_content_illegal"=> fake()->boolean,
            "decision_facts"=> "facts about the decision",
            "automated_detection"=> fake()->randomElement(["Yes","No"]),
            "automated_decision"=> fake()->randomElement(["Yes","No"]),

            "url" => fake()->url,
        ];

        $data['decision_ground'] = fake()->randomElement(["ILLEGAL_CONTENT","INCOMPATIBLE_CONTENT"]);
        if ($data['decision_ground'] == "ILLEGAL_CONTENT"){
            $data['illegal_content_legal_ground'] = fake()->text;
            $data['illegal_content_explanation'] = fake()->text;
        }
        if ($data['decision_ground'] == "INCOMPATIBLE_CONTENT"){
            $data['incompatible_content_ground'] = fake()->text;
            $data['incompatible_content_explanation'] = fake()->text;
        }

        $data['source'] = fake()->randomElement(["SOURCE_ARTICLE_16","SOURCE_TRUSTED_FLAGGER","SOURCE_VOLUNTARY"]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('app.remote_token'),
            'accept' => 'application/json',
            'content-type' => 'application/json'
        ])->post($url, $data);

        if ($response->failed()) {
            Log::info('[ERROR] '.$this->id . ': ' . $response);
        };

    }
}
