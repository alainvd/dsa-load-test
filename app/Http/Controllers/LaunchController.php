<?php

namespace App\Http\Controllers;

use App\Jobs\FireStatement;
use App\Jobs\FireSingleStatement;
use App\Jobs\DispatchSingleStatements;
use App\Jobs\DispatchBatchStatements;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LaunchController extends Controller
{
    public function fire(Request $request)
    {
        $limit = (int) $request->get('limit');

        // Validate the limit to prevent excessive load
        if ($limit > 10000) {
            return back()->with('status', 'Error: Maximum limit is 10,000 batches');
        }

        // Dispatch a single background job that will handle creating all the batch jobs
        DispatchBatchStatements::dispatch($limit);

        return back()->with('status', "Processing $limit batches in the background");
    }

    public function fireSingle(Request $request)
    {
        $limit = (int) $request->get('limit');

        // Validate the limit to prevent excessive load
        if ($limit > 1000) {
            return back()->with('status', 'Error: Maximum limit is 100,000 statements');
        }

        // Dispatch a single background job that will handle creating all the individual statement jobs
        foreach(range(1, 100) as $i) {
            DispatchSingleStatements::dispatch($limit);
        }
        return back()->with('status', "Processing $limit single statements in the background");
    }
}
