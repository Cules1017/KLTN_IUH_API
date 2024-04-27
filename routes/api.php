<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\FreelancerController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\SystermConfigController;
use App\Http\Controllers\TaskController;
use App\Models\Freelancer;
use App\Models\Job;
use App\Models\Skill;
use App\Services\JobService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

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
Route::group(['prefix' => env('APP_VERSION', 'v1'), 'namespace' => 'App\Http\Controllers','middleware' => ['logmd']], function () {
    Route::group(
        ['middleware' => ['api']],
        function () {
            Route::post('/send-otp', 'AuthController@sendOtp')->middleware('checktoken');
            Route::post('/verify-otp', 'AuthController@verifyOtp')->middleware('checktoken');
            Route::post('/verify-citizen-identification-card', 'AuthController@CitizenIdentificationCardVerify')->middleware('checktoken');
            Route::post('/login', 'AuthController@login')->name('login');
            Route::post('/register', 'AuthController@register')->name('register');
            Route::get('/verify', 'AuthController@VerifyCodeEmail')->name('VerifyCodeEmail');
            Route::get('/login-with-google', 'AuthController@redirectToGoogle')->name('LoginWithGoogle')->where('driver', implode('|', config('auth.socialite.drivers')));
            Route::get('/google-callback', 'AuthController@handleGoogleCallback');
        }
    );
    Route::get('/linkstorage', function () {
        dd(public_path('../'));
    });
    Route::group(
        ['prefix' => 'administrator', 'middleware' => 'checktoken'], //
        function () {
            Route::get('/test', 'AuthController@login')->name('login');
            Route::group(
                ['prefix' => 'manager', 'middleware' => ['isAdmin', 'exceptionGuest']],
                function () {
                    Route::get('', [AdminController::class, 'index']);
                    Route::post('', [AdminController::class, 'store']);
                    Route::post('{id}', [AdminController::class, 'update']);
                    Route::delete('{id}', [AdminController::class, 'destroy']);
                }
            );
            // Route::get('skill', [SkillController::class, 'index']);
            Route::group(
                ['prefix' => 'skill', 'middleware' => ['isAdmin', 'exceptionGuest']],
                function () {
                    //Route::get('', [SkillController::class, 'index']);
                    Route::post('', [SkillController::class, 'store']);
                    Route::put('{id}', [SkillController::class, 'update']);
                    Route::delete('{id}', [SkillController::class, 'destroy']);
                }
            );
            Route::get('skill', [SkillController::class, 'index']);
            Route::group(
                ['prefix' => 'client', 'middleware' => ['isAdmin', 'exceptionGuest']],
                function () {
                    Route::get('', [ClientController::class, 'index']);
                    //Route::post('', [ClientController::class, 'store']);
                    Route::put('{id}', [ClientController::class, 'update']);
                    Route::delete('{id}', [ClientController::class, 'destroy']);
                }
            );
            Route::group(
                ['prefix' => 'freelancer', 'middleware' => ['isAdmin', 'exceptionGuest']],
                function () {
                    Route::get('', [FreelancerController::class, 'index']);
                    //Route::post('', [ClientController::class, 'store']);
                    Route::put('{id}', [FreelancerController::class, 'update']);
                    Route::delete('{id}', [FreelancerController::class, 'destroy']);
                }
            );
            Route::group(
                ['prefix' => 'systerm-config', 'middleware' => ['isAdmin', 'exceptionGuest']],
                function () {
                    Route::get('', [SystermConfigController::class, 'index']);
                    Route::post('', [SystermConfigController::class, 'store']);
                    Route::put('{id}', [SystermConfigController::class, 'update']);
                    Route::delete('{id}', [SystermConfigController::class, 'destroy']);
                }
            );
            Route::group(
                ['prefix' => 'report', 'middleware' => ['isAdmin', 'exceptionGuest']],
                function () {
                    Route::get('', [ReportController::class, 'index']);
                    Route::put('resolve/{id}', [ReportController::class, 'updateAdmin']);
                }
            );
            Route::group(
                ['prefix' => 'post', 'middleware' => ['isAdmin', 'exceptionGuest']],
                function () {
                    Route::get('', [ReportController::class, 'index']);
                    Route::put('resolve/{id}', [ReportController::class, 'updateAdmin']);
                }
            );
        }
    );
    Route::group(
        ['prefix' => 'client', 'middleware' => 'checktoken'], //
        function () {
            Route::group(
                ['prefix' => 'info', 'middleware' => ['isClient']],
                function () {
                    //Route::get('', [ClientController::class, 'index']);
                    //Route::post('', [ClientController::class, 'store']);
                    Route::post('update', [ClientController::class, 'updateForClient']);
                    Route::get('', [ClientController::class, 'getInfoClient']);
                }
            );
            Route::group(
                ['prefix' => 'job', 'middleware' => ['isClient']],
                function () {
                    Route::get('/my-jobs', [JobController::class, 'getMyPost']);
                    Route::post('/create-job', [JobController::class, 'createNewPost']);
                    Route::post('/update-jobs/{id}', [JobController::class, 'updateForClient']);
                    Route::delete('{id}', [JobController::class, 'destroy']);
                    
                }
            );
            Route::post('job/{id}/recruit-confirm', [JobController::class, 'recruitmentConfirmation']);
            Route::group(
                ['prefix' => 'freelancers', 'middleware' => ['isClient']],
                function () {
                    Route::get('/', [ClientController::class, 'getListFreelancer']);
                    Route::post('/invite', [ClientController::class, 'inviteJob']);
                    Route::post('/create-jobs', [JobController::class, 'createNewPost']);
                    Route::post('/update-jobs/{id}', [JobController::class, 'updateForClient']);
                    Route::delete('{id}', [JobController::class, 'destroy']);
                    Route::post('/{id}/recruit-confirm', [JobController::class, 'recruitmentConfirmation']);
                }
            );
            Route::group(
                ['prefix' => 'invite', 'middleware' => ['isFreelancer']],
                function () {
                    Route::post('/accept/{id}', [ClientController::class, 'acceptJob']);
                    Route::get('/list', [ClientController::class, 'getListInvite']);
                }
            );
        }
    );
    Route::group(
        ['prefix' => 'freelancer', 'middleware' => 'checktoken'], //
        function () {
            Route::group(
                ['prefix' => 'info','middleware' => ['isFreelancer']],
                function () {
                    Route::post('update', [FreelancerController::class, 'updateForFreelancer']);
                    Route::get('', [FreelancerController::class, 'getInfoUser']);
                }
            );
            Route::group(
                ['prefix' => 'job', 'middleware' => ['isFreelancer']],
                function () {
                    Route::post('update', [FreelancerController::class, 'updateForFreelancer']);
                    Route::get('', [JobController::class, 'getJobListForFreelancer']);
                    Route::post('apply', [JobController::class, 'FreelancerApplyJob']);
                    Route::get('applied', [JobController::class, 'getFreelancerAppliedJob']);
                }
            );
        }
    );
    Route::group(
        ['prefix' => 'job', 'middleware' => 'checktoken'],
        function () {
            Route::get('/{id}', [JobController::class, 'getDetails']);
            Route::get('/{id}/task', [JobController::class, 'getTaskByJob']);
            Route::post('/{id}/new-task', [JobController::class, 'addTask']);
            Route::post('/task/{id}/set-status', [JobController::class, 'freelancerSetStatus'])->middleware('isFreelancer');
            Route::post('/task/{id}/confirm-status', [JobController::class, 'clientConfirmStatus'])->middleware('isClient');
            Route::delete('/task/{id}', [JobController::class, 'destroyTask'])->middleware('isClient');
            Route::put('/task/{id}', [JobController::class, 'editTask']);
            Route::post('/task/{id}/add-cmt-task', [JobController::class, 'addCommentTask']);
            Route::delete('/task/{id}/delete-cmt-task', [JobController::class, 'deleteCommentTask']);
        }
    );
    Route::group(
        ['prefix' => 'notifications', 'middleware' => 'checktoken'], //
        function () {
            Route::get('', [NotificationController::class, 'index']);
            Route::post('', [NotificationController::class, 'store']);
            Route::put('/{id}', [NotificationController::class, 'update']);
            Route::put('/{id}/seen', [NotificationController::class, 'seen']);
        }
    );
    Route::group(
        ['prefix' => 'chat', 'middleware' => 'checktoken'], //
        function () {
            Route::post('new-chat', [ChatController::class, 'createNewRoomChat']);
            Route::get('', [ChatController::class, 'getMyChat']);
            Route::get('messages/{roomId}', [ChatController::class, 'getMessagesByRoomId']);
            Route::post('send-message', [ChatController::class, 'sendMessage']);
        }
    );
    Route::group(
        ['prefix' => 'mylist', 'middleware' => 'checktoken'], //
        function () {
            Route::post('/{id}', [FreelancerController::class, 'AddJob']);
            Route::get('/', [FreelancerController::class, 'GetMyJob']);
        }
    );
    Route::get('majors',[ClientController::class, 'getMajors'])->middleware('checktoken');
    Route::get('info-user','AuthController@getInfoUserById')->middleware('checktoken');
 
});
