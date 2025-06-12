<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MetricsController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/fire', [\App\Http\Controllers\LaunchController::class,'fire'])->name('fire');
Route::get('/single', function () {
    return view('single');
})->name('single');
Route::post('/fire-single', [\App\Http\Controllers\LaunchController::class,'fireSingle'])->name('fire-single');

Route::get('/metrics', [MetricsController::class, 'showMetrics'])->name('metrics');
Route::post('/metrics/truncate', [MetricsController::class, 'truncateResponses'])->name('metrics.truncate');
