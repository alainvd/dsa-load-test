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

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('app.remote_token'),
            'accept' => 'application/json',
            'content-type' => 'application/json'
        ])->post($url, [
            'title' => config('app.name') . " - " . $this->id . " - " . $this->faker->sentence(4),
            'body' => $this->faker->text,
            'language' => $this->faker->regexify('[A-Za-z0-9]{50}'),
            'date_sent' => Carbon::createMidnightDate($this->faker->date())->addHours($this->faker->numberBetween(3,23))->addMinutes($this->faker->numberBetween(0,59))->addSeconds($this->faker->numberBetween(0,59))->toDateTimeString(),
            'date_enacted' => Carbon::createMidnightDate($this->faker->date())->addHours($this->faker->numberBetween(3,23))->addMinutes($this->faker->numberBetween(0,59))->addSeconds($this->faker->numberBetween(0,59))->toDateTimeString(),
            'date_abolished' => Carbon::createMidnightDate($this->faker->date())->addHours($this->faker->numberBetween(3,23))->addMinutes($this->faker->numberBetween(0,59))->addSeconds($this->faker->numberBetween(0,59))->toDateTimeString(),
            'countries_list' => $this->faker->regexify('[A-Za-z0-9]{255}'),
            'source' => $this->faker->randomElement(["Article 16","voluntary own-initiative investigation"]),
            'payment_status' => $this->faker->randomElement(["suspension","termination","other"]),
            'restriction_type' => $this->faker->randomElement(["removed","disabled","demoted","other"]),
            'restriction_type_other' => $this->faker->text,
            'automated_detection' => $this->faker->randomElement(["Yes","No","Partial"]),
            'automated_detection_more' => $this->faker->text,
            'illegal_content_legal_ground' => $this->faker->regexify('[A-Za-z0-9]{255}'),
            'illegal_content_explanation' => $this->faker->text,
            'toc_contractual_ground' => $this->faker->regexify('[A-Za-z0-9]{255}'),
            'toc_explanation' => $this->faker->text,
            'redress' => $this->faker->randomElement(["Internal Mechanism","Out Of Court Settlement","Other"]),
            'redress_more' => $this->faker->text,
        ]);

        if ($response->failed()) {
            Log::info('[ERROR] '.$this->id . ': ' . $response);
        };

    }
}
