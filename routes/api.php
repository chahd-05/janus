<?php

use App\Http\Controllers\api\AutController;
use App\Http\Controllers\api\CategoryController;
use App\Http\Controllers\api\HabitController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('/habit', HabitController::class)->middleware('auth:sanctum');
Route::post('/register', [AutController::class, 'register']);
Route::post('/logout', [AutController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/login', [AutController::class, 'login']);