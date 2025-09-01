<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => response()->json(['ok' => true]));

// contoh endpoint yang butuh token Sanctum
Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    return $request->user();
});
