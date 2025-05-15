<?php

// routes/api.php

use App\Http\Controllers\BookController;
use App\Http\Controllers\BorrowController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    // Browse books (all authenticated users)
    Route::get('/books', [BookController::class, 'index']);

    // Borrow books
    Route::post('/books/{book}/borrow', [BorrowController::class, 'borrow']);

    // Return borrowed book
    Route::post('/borrowers/{borrow}/return', [BorrowController::class, 'returnBook']);

    // View own borrowings
    Route::get('/my-borrows', [BorrowController::class, 'myBorrows']);

    // Admin only (middleware applied in controller or here)
    Route::middleware('admin')->group(function () {
        Route::post('/books', [BookController::class, 'store']);
        Route::put('/books/{book}', [BookController::class, 'update']);
        Route::delete('/books/{book}', [BookController::class, 'destroy']);
    });
});
