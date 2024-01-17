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
        ['middleware' => ['api']],
        function () {
            Route::post('/login', 'AuthController@login')->name('login');
            Route::post('/register', 'AuthController@register')->name('register');
            Route::get('/verify', 'AuthController@VerifyCodeEmail')->name('VerifyCodeEmail');
            Route::get('/login-with-google', 'AuthController@redirectToGoogle')->name('LoginWithGoogle')->where('driver', implode('|', config('auth.socialite.drivers')));
            Route::get('/google-callback', 'AuthController@handleGoogleCallback');
        }
    );
    Route::group(
        ['middleware' => 'checktoken'],
        function () {
            Route::get('/test', 'AuthController@login')->name('login');
        }
    );
});
