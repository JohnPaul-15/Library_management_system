<?php

use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\StudentsController;
use App\Http\Controllers\Api\BorrowerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Book;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

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
    Route::get('/books/borrowed', [BookController::class, 'borrowed']);
    Route::get('/books/available', [BookController::class, 'available']);
    Route::post('/books/{book}/borrow', [BookController::class, 'borrow']);
    Route::post('/books/{book}/return', [BookController::class, 'return']);
    Route::apiResource('students', StudentsController::class);
    Route::apiResource('borrower', BorrowerController::class);

    // Admin routes
    Route::get('/books/all-borrowed', [BookController::class, 'allBorrowed'])->middleware('auth:sanctum');
    Route::get('/users', [UserController::class, 'index'])->middleware('auth:sanctum');
    Route::post('/users', [UserController::class, 'store'])->middleware('auth:sanctum');
    Route::get('/users/{user}', [UserController::class, 'show'])->middleware('auth:sanctum');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('auth:sanctum');
});

Route::middleware(['auth:sanctum', \App\Http\Middleware\AdminMiddleware::class])->group(function () {
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/debug/test-queries', [DashboardController::class, 'testQueries']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Test route for debugging
Route::get('/test/users', function () {
    $users = \App\Models\User::select('id', 'name', 'email', 'role', 'status')->get();
    return response()->json([
        'status' => 'success',
        'data' => $users
    ]);
});

// Debug endpoint to verify backend functionality
Route::get('/debug/test-endpoint', function() {
    try {
        // Test database connection
        DB::connection()->getPdo();
        
        // Test model queries
        $bookCount = Book::count();
        $userCount = User::count();
        
        return response()->json([
            'success' => true,
            'message' => 'Test endpoint is working',
            'data' => [
                'book_count' => $bookCount,
                'user_count' => $userCount,
                'database_connection' => 'OK',
                'environment' => config('app.env'),
                'debug_mode' => config('app.debug')
            ]
        ]);
    } catch (\Exception $e) {
        Log::error('Debug endpoint failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Test endpoint failed',
            'error' => $e->getMessage(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            'debug' => [
                'environment' => config('app.env'),
                'php_version' => phpversion(),
                'laravel_version' => app()->version()
            ]
        ], 500);
    }
});

// Test route for debugging user roles
Route::get('/test/user-role', function (Request $request) {
    try {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated',
                'user' => null
            ], 401);
        }

        $user = Auth::user();
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'is_admin' => $user->isAdmin(),
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at
            ]
        ]);
    } catch (\Exception $e) {
        Log::error('User role test failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Error checking user role',
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
})->middleware('auth:sanctum');

// Debug route for checking user role
Route::get('/debug/check-role', function (Request $request) {
    try {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated',
                'user' => null
            ], 401);
        }

        $user = Auth::user();
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'is_admin' => $user->isAdmin(),
                'raw_role' => $user->getRawOriginal('role'),
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error checking user role',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
})->middleware('auth:sanctum');

// Debug route for checking database tables
Route::get('/debug/check-tables', function () {
    try {
        $tables = [
            'users' => [
                'columns' => Schema::getColumnListing('users'),
                'count' => DB::table('users')->count(),
                'sample' => DB::table('users')->select('id', 'name', 'email', 'role', 'status')->first()
            ],
            'books' => [
                'columns' => Schema::getColumnListing('books'),
                'count' => DB::table('books')->count()
            ],
            'borrowers' => [
                'columns' => Schema::getColumnListing('borrowers'),
                'count' => DB::table('borrowers')->count()
            ]
        ];

        return response()->json([
            'success' => true,
            'tables' => $tables,
            'database_connection' => DB::connection()->getDatabaseName(),
            'migrations_status' => DB::table('migrations')->get(['migration', 'batch'])
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error checking database tables',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
})->middleware('auth:sanctum');
