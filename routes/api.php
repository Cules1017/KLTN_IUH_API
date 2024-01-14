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
Route::group(['prefix' => env('APP_VERSION','v1'), 'namespace' => 'App\Http\Controllers'], function () {
    Route::group(
        [],
        function () {
            Route::post('/login', 'AuthController@login')->name('login');
        }
    );
    Route::group(
        ['middleware' => 'checktoken'],
        function () {
            Route::get('/test', 'AuthController@login')->name('login');
        }
    );
});
