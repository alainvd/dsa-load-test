<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/fire', [\App\Http\Controllers\LaunchController::class,'fire'])->name('fire');
Route::get('/single', function () {
    return view('single');
})->name('single');
Route::post('/fire-single', [\App\Http\Controllers\LaunchController::class,'fireSingle'])->name('fire-single');
