<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('/auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::post('/forget-password', [AuthController::class, 'forgetPassword'])->middleware('throttle:6,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/refresh', [AuthController::class, 'refreshToken'])->middleware('throttle:10,1');
    Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware('signed')
        ->name('verification.verify');

    Route::middleware('jwt.auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::delete('/account', [AuthController::class, 'deleteAccount']);
        Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])
            ->middleware('throttle:6,1');
    });
});

Route::middleware('jwt.auth')->group(function () {
    Route::prefix('/project')->group(function () {
        Route::get('/list', [ProjectController::class, 'list']);
        Route::get('/list-filter', [ProjectController::class, 'listWithFilter']);
        Route::get('/show/{project}', [ProjectController::class, 'show']);
        Route::post('/store', [ProjectController::class, 'store']);
        Route::put('/update/{project}', [ProjectController::class, 'update']);
        Route::delete('/destroy/{project}', [ProjectController::class, 'destroy']);
    });

    Route::prefix('/task')->group(function () {
        Route::get('/lists/{project}', [TaskController::class, 'list']);
        Route::get('/{task}', [TaskController::class, 'show']);
        Route::post('/store', [TaskController::class, 'store']);
        Route::put('/update/{task}', [TaskController::class, 'update']);
        Route::delete('/destroy/{task}', [TaskController::class, 'destroy']);
    });
});
