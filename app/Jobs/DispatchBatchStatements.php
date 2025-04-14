<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DispatchBatchStatements implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $total;
    private $batchSize;

    /**
     * Create a new job instance.
     *
     * @param int $total Total number of batches to dispatch
     * @param int $batchSize Number of jobs to dispatch in each processing batch
     * @return void
     */
    public function __construct(int $total, int $batchSize = 100)
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
        Log::info("Starting to dispatch {$this->total} batch statements");

        // Process in smaller batches to avoid memory issues
        for ($i = 1; $i <= $this->total; $i++) {
            FireStatement::dispatch($i);

            // Add a small delay every batch to prevent overwhelming the queue
            if ($i % $this->batchSize === 0) {
                Log::info("Dispatched {$i} of {$this->total} batch statements");
                sleep(3); //
            }
        }

        Log::info("Completed dispatching all {$this->total} batch statements");
    }
}
