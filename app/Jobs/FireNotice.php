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


class FireNotice implements ShouldQueue
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

        $companies =          [
            "Youtube",
            "Google",
            "Amazon",
            "Apple",
            "Microsoft",

            "Tencent",
            "Shopify",
            "Facebook",
            "Twitter",
            "Reddit",

            "TikTok",
            "Pinterest",
            "Instagram",
            "LinkedIn",
            "Snapchat",

            "Dailymotion",
            "Vimeo",
            "Flickr",
            "WeChat",
            "Tumblr"

        ];

        $date_sent = Carbon::createMidnightDate($this->faker->dateTimeThisYear())->addHours($this->faker->numberBetween(3,23))->addMinutes($this->faker->numberBetween(0,59))->addSeconds($this->faker->numberBetween(0,59));



        $data = [
            'title' => $this->faker->randomElement($companies) . " - Claim #" . rand(10000,999999),
            'body' => $this->createStatement(),
            'language' => $this->faker->randomElement(["en","fr"]),
            'date_sent' => $date_sent->toDateTimeString(),
            'countries_list' => $this->faker->randomElements(["IE","DE","FR","NL","BE","US"]),
            'source' => $this->faker->randomElement(["Article 16","voluntary own-initiative investigation"]),
            'payment_status' => $this->faker->randomElement(["suspension","termination","other"]),
            'automated_detection' => $this->faker->randomElement(["Yes","No","Partial"]),

        ];

        if(rand(0,100)>10){
            $months = rand(1,10);
            $data['date_enacted'] = $date_sent->toDateTimeString();
            $data['date_abolished'] = $date_sent->addMonths($months)->toDateTimeString();
        }

        if(rand(0,100)>80){
            $data['illegal_content_legal_ground'] = $this->faker->text;
            $data['illegal_content_explanation'] = $this->faker->text;
        }

        if(rand(0,100)>80){
            $data['toc_contractual_ground'] = $this->faker->text;
            $data['toc_explanation'] = $this->faker->text;
        }

        if(rand(0,100)>80){
            $data['automated_detection_more'] = fake()->text;
        }

        $data['restriction_type'] = fake()->randomElement(["removed","disabled","demoted","other"]);
        if ($data['restriction_type'] == "other"){
            $data['restriction_type_other'] = fake()->text;
        }

        $data['redress'] = fake()->randomElement(["Internal Mechanism","Out Of Court Settlement","Other"]);
        if ($data['redress'] == "Other"){
            $data['redress_more'] = fake()->text;
        }


        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('app.remote_token'),
            'accept' => 'application/json',
            'content-type' => 'application/json'
        ])->post($url, $data);

        if ($response->failed()) {
            Log::info('[ERROR] '.$this->id . ': ' . $response);
        };

    }

    private function createStatement()
    {
        return "Dear valued user,

We regret to inform you that the content you have posted on our platform has been removed due to a violation of our terms of service. Specifically, the content in question was found to be in violation of our policies on hate speech and promoting violence.

We understand that you may be disappointed or frustrated by this decision, but it is important to us to maintain a safe and inclusive environment for all of our users. We do not allow content that promotes or condones hate or violence against any individual or group, as it goes against the values and principles of our community.

We encourage you to review our terms of service and community guidelines in the future to ensure that your future posts are in compliance with our policies. We appreciate your understanding and cooperation.";
    }
}
