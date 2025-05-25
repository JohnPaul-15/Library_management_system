<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Borrower;
use Illuminate\Support\Facades\Log;

class BorrowedBookController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            Log::info('Fetching borrowed books for user', ['user_id' => $user->id]);
            
            $borrowedBooks = Book::whereHas('borrowers', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->whereNull('date_return');
            })->with(['borrowers' => function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->whereNull('date_return')
                      ->select('id', 'book_id', 'user_id', 'date_borrowed', 'due_date');
            }])->get()->map(function ($book) {
                $borrower = $book->borrowers->first();
                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'isbn' => $book->isbn,
                    'borrowed_at' => $borrower->date_borrowed,
                    'return_date' => $borrower->due_date
                ];
            });
            
            return response()->json([
                'status' => 'success',
                'data' => $borrowedBooks
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching borrowed books', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch borrowed books',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 