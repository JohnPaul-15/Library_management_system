<?php

namespace App\Http\Controllers\Api;

use App\Models\Book;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\BookResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Borrower;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookController extends Controller
{
    public function index()
    {
        $books = Book::withCount(['borrowers as borrowed_copies' => function($query) {
            $query->whereNull('date_return');
        }])->get();

        return response()->json([
            'success' => true,
            'data' => $books->map(function ($book) {
                $availableCopies = $book->total_copies - $book->borrowed_copies;
                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'publisher' => $book->publisher,
                    'total_copies' => $book->total_copies,
                    'available_copies' => $availableCopies,
                    'status' => $availableCopies > 0 ? 'Available' : 'Not Available'
                ];
            })
        ]);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "title" => "required|string|max:255",
            "author" => "required|string|max:255",
            "publisher" => "required|string|max:255",
            "total_copies" => "required|integer|min:1",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),    
            ], 422);
        }

        try {
            DB::beginTransaction();

            $book = Book::create([
                'title' => $request->title,
                'author' => $request->author,
                'publisher' => $request->publisher,
                'total_copies' => (int)$request->total_copies,
                'available_copies' => (int)$request->total_copies,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Book created successfully',
                'data' => new BookResource($book)
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create book', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create book'
            ], 500);
        }
    }

    public function show(Book $book)
    {
        if($book)
        {
            return new BookResource($book);
        }
        else 
        {
            return response()->json(['message' => 'Book not found'], 404);
        }
    }

    public function update(Request $request, Book $book)
    {
        $validator = Validator::make($request->all(), [
            "title" => "required|string|max:255",
            "author" => "required|string|max:255",
            "publisher" => "required|string|max:255",
            "total_copies" => "required|integer|min:1",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),    
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Calculate new available copies if total copies changes
            $availableCopies = $book->available_copies;
            if ((int)$request->total_copies !== $book->total_copies) {
                $difference = (int)$request->total_copies - $book->total_copies;
                $availableCopies = max(0, $availableCopies + $difference);
            }

            $book->update([
                'title' => $request->title,
                'author' => $request->author,
                'publisher' => $request->publisher,
                'total_copies' => (int)$request->total_copies,
                'available_copies' => $availableCopies,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Book updated successfully',
                'data' => new BookResource($book)
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update book', [
                'book_id' => $book->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update book'
            ], 500);
        }
    }

    public function destroy(Book $book)
    {
        $book->delete();
        return response()->json([
            'message' => 'Book deleted successfully',
        ], 200);
    }

    public function borrowed()
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $borrowedBooks = Book::whereHas('borrowers', function ($query) use ($userId) {
            $query->where('user_id', $userId)
                  ->whereNull('date_return');
        })->with(['borrowers' => function ($query) use ($userId) {
            $query->where('user_id', $userId)
                  ->whereNull('date_return')
                  ->select('id', 'book_id', 'user_id', 'date_borrowed', 'due_date');
        }])->get();

        return response()->json([
            'success' => true,
            'data' => $borrowedBooks->map(function ($book) {
                $borrower = $book->borrowers->first();
                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'isbn' => $book->isbn,
                    'status' => 'borrowed',
                    'borrowed_at' => $borrower->date_borrowed,
                    'return_date' => $borrower->due_date
                ];
            })
        ]);
    }

    public function allBorrowed()
    {
        \Log::info('Request headers:', request()->headers->all());
        \Log::info('Bearer token:', [request()->bearerToken()]);

        if (!Auth::check()) {
            \Log::warning('Unauthenticated access attempt');
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        if (!Auth::user()->isAdmin()) {
            \Log::warning('Non-admin access attempt', [
                'user_id' => Auth::id(),
                'user_role' => Auth::user()->role
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Admin access required.'
            ], 403);
        }

        try {
            $borrowedBooks = Book::whereHas('borrowers', function ($query) {
                $query->whereNull('date_return');
            })->with(['borrowers' => function ($query) {
                $query->whereNull('date_return')
                      ->with('user:id,name,email')
                      ->select('id', 'book_id', 'user_id', 'date_borrowed', 'due_date');
            }])->get();

            \Log::info('Successfully retrieved borrowed books', [
                'count' => $borrowedBooks->count(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'data' => $borrowedBooks->map(function ($book) {
                    $borrower = $book->borrowers->first();
                    return [
                        'id' => $book->id,
                        'title' => $book->title,
                        'author' => $book->author,
                        'isbn' => $book->isbn,
                        'status' => 'borrowed',
                        'borrowed_by' => $borrower->user->name,
                        'borrower_email' => $borrower->user->email,
                        'borrowed_at' => $borrower->date_borrowed,
                        'return_date' => $borrower->due_date
                    ];
                })
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching borrowed books', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch borrowed books'
            ], 500);
        }
    }

    public function available()
    {
        $availableBooks = Book::where('available_copies', '>', 0)->get();
        
        return response()->json([
            'success' => true,
            'data' => $availableBooks->map(function ($book) {
                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author,
                    'isbn' => $book->isbn,
                    'status' => 'available'
                ];
            })
        ]);
    }

    public function borrow(Book $book)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        if ($book->available_copies <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'No copies available for borrowing'
            ], 422);
        }

        // Check if user already has this book borrowed
        $existingBorrow = Borrower::where('user_id', $userId)
            ->where('book_id', $book->id)
            ->whereNull('date_return')
            ->first();

        if ($existingBorrow) {
            return response()->json([
                'success' => false,
                'message' => 'You have already borrowed this book'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create borrow record
            $borrower = Borrower::create([
                'user_id' => $userId,
                'book_id' => $book->id,
                'date_borrowed' => now(),
                'due_date' => now()->addDays(14), // 2 weeks borrowing period
            ]);

            // Update book available copies
            $book->decrement('available_copies');

            DB::commit();

            Log::info('Book borrowed successfully', [
                'book_id' => $book->id,
                'user_id' => $userId,
                'borrower_id' => $borrower->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Book borrowed successfully',
                'data' => [
                    'id' => $book->id,
                    'title' => $book->title,
                    'borrowed_at' => $borrower->date_borrowed,
                    'return_date' => $borrower->due_date
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to borrow book', [
                'book_id' => $book->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to borrow book'
            ], 500);
        }
    }

    public function return(Book $book)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $borrower = Borrower::where('user_id', $userId)
            ->where('book_id', $book->id)
            ->whereNull('date_return')
            ->first();

        if (!$borrower) {
            return response()->json([
                'success' => false,
                'message' => 'You have not borrowed this book'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Update borrow record
            $borrower->update([
                'date_return' => now()
            ]);

            // Ensure we don't exceed total copies
            if ($book->available_copies < $book->total_copies) {
                $book->increment('available_copies');
            }

            DB::commit();

            Log::info('Book returned successfully', [
                'book_id' => $book->id,
                'user_id' => $userId,
                'borrower_id' => $borrower->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Book returned successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to return book', [
                'book_id' => $book->id,
                'user_id' => $userId,
                'borrower_id' => $borrower->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to return book'
            ], 500);
        }
    }

    public function availableBooks()
    {
        try {
            $availableBooks = Book::where('available_copies', '>', 0)->get();
            
            return response()->json([
                'success' => true,
                'data' => $availableBooks->map(function ($book) {
                    return [
                        'id' => $book->id,
                        'title' => $book->title,
                        'author' => $book->author,
                        'isbn' => $book->isbn,
                        'status' => 'available'
                    ];
                })
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching available books: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch available books'
            ], 500);
        }
    }
}
