<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/home', [\App\Http\Controllers\Controller::class, 'home']);
Route::get('/dinner/stats', [\App\Http\Controllers\Controller::class, 'dinnerStats']);
Route::post('/dinner/{uid}', [\App\Http\Controllers\Controller::class, 'dinner']);

Route::post('/gather/{platform}', [\App\Http\Controllers\Controller::class, 'gather']);
