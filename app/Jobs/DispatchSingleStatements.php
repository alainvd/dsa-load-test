<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DispatchSingleStatements implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $total;
    private $batchSize;

    /**
     * Create a new job instance.
     *
     * @param int $total Total number of statements to dispatch
     * @param int $batchSize Number of jobs to dispatch in each batch
     * @return void
     */
    public function __construct(int $total, int $batchSize = 10)
    {
        $this->total = $total;
        $this->batchSize = $batchSize;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Starting to dispatch {$this->total} single statements");

        // Process in smaller batches to avoid memory issues
        for ($i = 1; $i <= $this->total; $i++) {
            FireSingleStatement::dispatch($i);

            // Add a small delay every batch to prevent overwhelming the queue
            if ($i % $this->batchSize === 0) {
                Log::info("Dispatched {$i} of {$this->total} single statements");
            }
        }

        Log::info("Completed dispatching all {$this->total} single statements");
    }
}
