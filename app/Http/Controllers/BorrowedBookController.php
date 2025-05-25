<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Borrower;

class BorrowedBookController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            // Using your borrowers table
            $borrowedBooks = Borrower::with('book')
                ->where('user_id', $user->id)
                ->whereNull('date_return')
                ->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $borrowedBooks
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch borrowed books',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 