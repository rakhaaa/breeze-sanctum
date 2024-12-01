<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\UserController;

Route::middleware('auth:sanctum')->group(function () {
    Route::resource('users', UserController::class)->middleware('role:admin');
    Route::resource('todos', TodoController::class)->middleware('role:user,admin');
});