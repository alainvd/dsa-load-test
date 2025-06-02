<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

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
        // Force delete and reset all cache keys to ensure clean state
        Cache::forget('batch_processing_start');
        Cache::forget('batch_processing_end');
        Cache::forget('batch_processed_count');
        Cache::forget('total_batch_count');
        
        // Now set the initial values
        Cache::put('batch_processing_start', null, now()->addHours(1));
        Cache::put('batch_processing_end', null, now()->addHours(1));
        Cache::put('batch_processed_count', 0, now()->addHours(1));
        
        // Store the total number of batches for later reference
        Cache::put('total_batch_count', $this->total, now()->addHours(1));
        
        // Log that we're starting to dispatch jobs
        Log::info("[METRICS] Starting to dispatch {$this->total} batch statements");

        // Process in smaller batches to avoid memory issues
        for ($i = 1; $i <= $this->total; $i++) {
            FireStatement::dispatch($i);

            // Add a small delay every batch to prevent overwhelming the queue
            if ($i % $this->batchSize === 0) {
                sleep(3); // Small delay to prevent overwhelming the queue
            }
        }
        
        Log::info("[METRICS] Completed dispatching all {$this->total} batch statements");
    }
}
