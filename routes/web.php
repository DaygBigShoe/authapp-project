<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });


Route::get('/register-user/validate/{data}', [ApiController::class, 'register_user_validate']);
Route::post('/register-user/retrieve-pin', [ApiController::class, 'register_retrieve_pin']);

Route::get('/login-user/validate/{data}', [ApiController::class, 'login_user_validate']);
Route::post('/login-user/retrieve-pin', [ApiController::class, 'login_retrieve_pin']);
