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
        // Force delete and reset all cache keys to ensure clean state
        Cache::forget('single_processing_start');
        Cache::forget('single_processing_end');
        Cache::forget('single_processed_count');
        Cache::forget('total_single_count');
        
        // Now set the initial values
        Cache::put('single_processing_start', null, now()->addHours(1));
        Cache::put('single_processing_end', null, now()->addHours(1));
        Cache::put('single_processed_count', 0, now()->addHours(1));
        
        // Store the total number of single statements for later reference
        Cache::put('total_single_count', $this->total, now()->addHours(1));
        
        // Log that we're starting to dispatch jobs
        Log::info("[METRICS] Starting to dispatch {$this->total} single statements");

        // Process in smaller batches to avoid memory issues
        for ($i = 1; $i <= $this->total; $i++) {
            FireSingleStatement::dispatch($i);

            // Add a small delay every batch to prevent overwhelming the queue
            if ($i % $this->batchSize === 0) {
                // No intermediate logging to reduce log verbosity
            }
        }
        
        Log::info("[METRICS] Completed dispatching all {$this->total} single statements");
    }
}
