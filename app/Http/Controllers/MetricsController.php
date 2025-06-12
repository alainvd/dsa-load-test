<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StatementResponse;
use Carbon\Carbon;

class MetricsController extends Controller
{
    public function showMetrics()
    {
        $count = StatementResponse::count();
        $firstRecord = StatementResponse::orderBy('response_created_at', 'asc')->first();
        $lastRecord = StatementResponse::orderBy('response_created_at', 'desc')->first();

        $duration = null;
        if ($firstRecord && $lastRecord) {
            $firstTime = Carbon::parse($firstRecord->response_created_at);
            $lastTime = Carbon::parse($lastRecord->response_created_at);
            $duration = $lastTime->diffForHumans($firstTime, true);
        }

        return view('metrics', [
            'count' => $count,
            'firstRecordTime' => $firstRecord ? Carbon::parse($firstRecord->response_created_at)->toDateTimeString() : null,
            'lastRecordTime' => $lastRecord ? Carbon::parse($lastRecord->response_created_at)->toDateTimeString() : null,
            'duration' => $duration,
        ]);
    }

    public function truncateResponses()
    {
        StatementResponse::truncate();

        return redirect()->route('metrics')->with('success', 'All statement responses have been deleted.');
    }

    //
}
