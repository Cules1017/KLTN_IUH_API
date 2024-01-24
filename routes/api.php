<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\FreelancerController;
use App\Http\Controllers\SkillController;
use App\Models\Skill;
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

Route::group(['prefix' => env('APP_VERSION', 'v1'), 'namespace' => 'App\Http\Controllers'], function () {
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
        ['prefix' => 'administrator','middleware' => 'checktoken'], //
        function () {
            Route::get('/test', 'AuthController@login')->name('login');
            Route::group(
                ['prefix' => 'manager', 'middleware' => ['isAdmin','exceptionGuest']],
                function () {
                    Route::get('', [AdminController::class, 'index']);
                    Route::post('', [AdminController::class, 'store']);
                    Route::post('{id}', [AdminController::class, 'update']);
                    Route::delete('{id}', [AdminController::class, 'destroy']);
                }
            );
            Route::group(
                ['prefix' => 'skill', 'middleware' => ['isAdmin','exceptionGuest']],
                function () {
                    Route::get('', [SkillController::class, 'index']);
                    Route::post('', [SkillController::class, 'store']);
                    Route::put('{id}', [SkillController::class, 'update']);
                    Route::delete('{id}', [SkillController::class, 'destroy']);
                }
            );
            Route::group(
                ['prefix' => 'client', 'middleware' => ['isAdmin','exceptionGuest']],
                function () {
                    Route::get('', [ClientController::class, 'index']);
                    //Route::post('', [ClientController::class, 'store']);
                    Route::put('{id}', [ClientController::class, 'update']);
                    Route::delete('{id}', [ClientController::class, 'destroy']);
                }
            );
            Route::group(
                ['prefix' => 'freelancer', 'middleware' => ['isAdmin','exceptionGuest']],
                function () {
                    Route::get('', [FreelancerController::class, 'index']);
                    //Route::post('', [ClientController::class, 'store']);
                    Route::put('{id}', [FreelancerController::class, 'update']);
                    Route::delete('{id}', [FreelancerController::class, 'destroy']);
                }
            );
            
        }
    );
});
