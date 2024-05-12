<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\AuthController;
// use ParagonIE\Halite\KeyFactory;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route::get('/gen-key', function (Request $request) {
//     $encKey = KeyFactory::generateEncryptionKey();
//     KeyFactory::save($encKey, '../enckey.key');
//     return response()->json("saved");
// });

// Auth
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// App
Route::get('/account', [ApiController::class, 'account'])->middleware('auth:sanctum');
Route::get('/users', [ApiController::class, 'users'])->middleware('auth:sanctum');

Route::post('/register-user', [ApiController::class, 'register_user'])->middleware('auth:sanctum');
Route::post('/register-user/pin-validation', [ApiController::class, 'register_user_pin_validation'])->middleware('auth:sanctum');

Route::post('/login-user', [ApiController::class, 'login_user'])->middleware('auth:sanctum');
Route::post('/login-user/pin-validation', [ApiController::class, 'login_user_pin_validation'])->middleware('auth:sanctum');
