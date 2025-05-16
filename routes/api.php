<?php

use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\StudentsController;
use App\Http\Controllers\Api\BorrowerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

route::post('/register', [AuthController::class, 'register']);
route::post('/login', [AuthController::class, 'login']);

route::group(['middleware' => 'auth:sanctum'], function () {
    route::middleware('auth:sanctum')->get('/profile', [AuthController::class, 'profile']);
    route::get('/logout', [AuthController::class, 'logout']);
});

route::apiResource('books', BookController::class);
route::apiResource('students', StudentsController::class);
route::apiResource('borrower', BorrowerController::class);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
