<?php

use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\StudentsController;
use App\Http\Controllers\Api\BorrowerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

route::apiResource('books', BookController::class);
route::apiResource('students', StudentsController::class);
route::apiResource('borrower', BorrowerController::class);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
