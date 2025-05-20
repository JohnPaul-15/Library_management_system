<?php

use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\StudentsController;
use App\Http\Controllers\Api\BorrowerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\UserController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::get('/logout', [AuthController::class, 'logout']);

    // Resource routes
    Route::apiResource('books', BookController::class);
    Route::apiResource('students', StudentsController::class);
    Route::apiResource('borrower', BorrowerController::class);

    // Admin only routes
    Route::middleware('can:viewAny,App\Models\User')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
