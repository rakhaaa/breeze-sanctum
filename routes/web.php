<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});
 
Route::post('/tokens/create', function (Request $request) {
    $token = $request->user()->createToken($request->token_name);
 
    return response()->json(['token' => $token->plainTextToken], 200);
});

Route::get('/token', function (Request $request) {
    // $token = $request->session()->token();
 
    $token = csrf_token();
 
    return $token;
});

require __DIR__.'/auth.php';
