<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\User;
use App\Models\Borrower;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Verify database connection
            DB::connection()->getPdo();

            // Log the start of the request
            Log::info('Dashboard stats request started', [
                'user_id' => Auth::id(),
                'user_role' => Auth::user()?->role,
                'is_admin' => Auth::user()?->isAdmin(),
                'auth_check' => Auth::check(),
                'request_headers' => request()->headers->all(),
                'bearer_token' => request()->bearerToken()
            ]);

            // Verify admin access
            if (!Auth::check() || !Auth::user()->isAdmin()) {
                Log::warning('Non-admin access attempt to dashboard stats', [
                    'user_id' => Auth::id(),
                    'user_role' => Auth::user()?->role,
                    'is_admin' => Auth::user()?->isAdmin()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.'
                ], 403);
            }

            // Use raw database values before casting for logging
            $rawTotalBooks = Book::count();
            $rawTotalUsers = User::count();
            $rawAvailableBooks = Book::where('available_copies', '>', 0)->count();
            $rawBorrowedBooks = Borrower::whereNull('date_return')->count();

            Log::info('Raw database values:', [
                'totalBooks' => $rawTotalBooks,
                'totalUsers' => $rawTotalUsers,
                'availableBooks' => $rawAvailableBooks,
                'borrowedBooks' => $rawBorrowedBooks,
                'queries' => DB::getQueryLog() ?? []
            ]);

            $stats = [
                'totalBooks' => (int)$rawTotalBooks,
                'totalUsers' => (int)$rawTotalUsers,
                'availableBooks' => (int)$rawAvailableBooks,
                'borrowedBooks' => (int)$rawBorrowedBooks,
            ];

            DB::commit();

            // Log successful response
            Log::info('Dashboard stats request completed successfully', [
                'user_id' => Auth::id(),
                'user_role' => Auth::user()->role,
                'stats' => $stats
            ]);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Dashboard stats request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'user_role' => Auth::user()?->role
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard stats',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
} 