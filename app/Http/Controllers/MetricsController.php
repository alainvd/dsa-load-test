<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StatementResponse;
use App\Models\ApiError;
use Carbon\Carbon;

class MetricsController extends Controller
{
    public function showMetrics()
    {
        $count = StatementResponse::count();
        $firstRecord = StatementResponse::orderBy('response_created_at', 'asc')->first();
        $lastRecord = StatementResponse::orderBy('response_created_at', 'desc')->first();

        $duration = null;
        $durationInSeconds = null;
        if ($firstRecord && $lastRecord) {
            $firstTime = Carbon::parse($firstRecord->response_created_at);
            $lastTime = Carbon::parse($lastRecord->response_created_at);
            $duration = $lastTime->diffForHumans($firstTime, true);
            $durationInSeconds = $firstTime->diffInSeconds($lastTime);
        }

                $apiErrorCount = ApiError::count();
        $apiErrorsByStatus = ApiError::select('status_code', \DB::raw('count(*) as total'))
            ->groupBy('status_code')
            ->orderBy('total', 'desc')
            ->get();

        return view('metrics', [
            'count' => $count,
            'firstRecordTime' => $firstRecord ? Carbon::parse($firstRecord->response_created_at)->toDateTimeString() : null,
            'lastRecordTime' => $lastRecord ? Carbon::parse($lastRecord->response_created_at)->toDateTimeString() : null,
                        'duration' => $duration,
            'durationInSeconds' => $durationInSeconds,
            'apiErrorCount' => $apiErrorCount,
            'apiErrorsByStatus' => $apiErrorsByStatus,
        ]);
    }

    public function truncateResponses()
    {
        StatementResponse::truncate();
        ApiError::truncate();

        return redirect()->route('metrics')->with('success', 'All statement responses and API error logs have been deleted.');
    }

    //
}
