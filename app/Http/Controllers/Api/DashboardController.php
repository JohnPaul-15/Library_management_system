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
                'auth_check' => Auth::check()
            ]);

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

            // Prepare response
            $response = [
                'success' => true,
                'data' => $stats,
                'meta' => [
                    'execution_time' => microtime(true) - LARAVEL_START,
                    'memory_usage' => memory_get_usage(true) / 1024 / 1024 . 'MB'
                ]
            ];

             // Log the response array before JSON encoding
            Log::info('Response array before encoding:', $response);

            // Try to encode the response and log any errors
            $jsonString = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
            if ($jsonString === false) {
                Log::error('JSON encoding failed', [
                    'error' => json_last_error_msg(),
                    'error_code' => json_last_error(),
                    'data' => $response
                ]);
                throw new \RuntimeException('Failed to encode response data: ' . json_last_error_msg());
            }

             // Remove any potential BOM or whitespace
            $jsonString = trim($jsonString);
            if (substr($jsonString, 0, 3) === "\xEF\xBB\xBF") {
                $jsonString = substr($jsonString, 3);
            }

            // Verify the JSON is valid by decoding and re-encoding
            $decoded = json_decode($jsonString, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Invalid JSON after encoding', [
                    'error' => json_last_error_msg(),
                    'json_string' => $jsonString
                ]);
                throw new \RuntimeException('Generated invalid JSON: ' . json_last_error_msg());
            }

            // Re-encode to ensure clean output
            // $jsonString = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            // Log detailed information about the JSON string
            Log::info('Final JSON string details:', [
                'json' => $jsonString,
                'length' => strlen($jsonString),
                'first_10_chars' => substr($jsonString, 0, 10),
                'last_10_chars' => substr($jsonString, -10),
                'hex_dump' => implode(' ', array_map(
                    function($char) { return sprintf('%02X', ord($char)); },
                    str_split(substr($jsonString, 0, 50))
                ))
            ]);

            // Create and return JsonResponse
            $response = response()->json($decoded)
                ->header('X-Content-Type-Options', 'nosniff')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

            Log::info('Dashboard stats request completed successfully');

            return $response;

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction on error
            Log::error('Dashboard stats error', [
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'user_role' => Auth::user()?->role,
                'is_admin' => Auth::user()?->isAdmin(),
                'auth_check' => Auth::check(),
                'request_headers' => request()->headers->all(),
                'request_method' => request()->method(),
                'request_url' => request()->fullUrl()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard stats',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'debug_info' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ] : null
            ], 500);
        }
    }
} 