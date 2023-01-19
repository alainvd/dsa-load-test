<?php

namespace App\Http\Controllers;

use App\Jobs\FireNotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LaunchController extends Controller
{
    public function fire(Request $request)
    {
        $limit = $request->get('limit');
        for ($i=1; $i<=$limit; $i++){
            FireNotice::dispatch($i);
        }

        return back()->with('status', $limit . ' Jobs dispatched');
    }
}
