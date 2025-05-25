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
                    'isbn' => $book->isbn,
                    'total_copies' => $book->total_copies,
                    'available_copies' => $availableCopies,
                    'status' => $availableCopies > 0 ? 'available' : 'borrowed',
                    'borrowed_by' => $book->getCurrentBorrower()?->user_id
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
                    'status' => 'available',
                    'available_copies' => $book->available_copies
                ];
            })
        ]);
    }

    public function borrow(Book $book, Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'copies' => 'required|integer|min:1',
            'return_date' => 'required|date|after:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $numCopies = $request->copies;
        $returnDate = $request->return_date;

        if (!$book->canBorrowCopies($numCopies)) {
            return response()->json([
                'success' => false,
                'message' => "Only {$book->available_copies} copies available for borrowing"
            ], 422);
        }

        // Check if user already has this book borrowed
        $currentBorrowedCopies = $book->getBorrowedCopiesByUser($userId);
        if ($currentBorrowedCopies > 0) {
            return response()->json([
                'success' => false,
                'message' => "You have already borrowed {$currentBorrowedCopies} copies of this book"
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create borrow records for each copy
            for ($i = 0; $i < $numCopies; $i++) {
                Borrower::create([
                    'user_id' => $userId,
                    'book_id' => $book->id,
                    'date_borrowed' => now(),
                    'due_date' => $returnDate,
                ]);
            }

            // Update book available copies
            $book->decrement('available_copies', $numCopies);

            DB::commit();

            Log::info('Books borrowed successfully', [
                'book_id' => $book->id,
                'user_id' => $userId,
                'copies' => $numCopies,
                'return_date' => $returnDate
            ]);

            return response()->json([
                'success' => true,
                'message' => "Successfully borrowed {$numCopies} copies",
                'data' => [
                    'id' => $book->id,
                    'title' => $book->title,
                    'copies' => $numCopies,
                    'borrowed_at' => now(),
                    'return_date' => $returnDate
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to borrow books', [
                'book_id' => $book->id,
                'user_id' => $userId,
                'copies' => $numCopies,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to borrow books'
            ], 500);
        }
    }

    public function return($id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $book = Book::findOrFail($id);
            $borrow = Borrower::where('book_id', $id)
                ->where('user_id', $user->id)
                ->whereNull('date_return')
                ->first();

            if (!$borrow) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have not borrowed this book'
                ], 422);
            }

            DB::beginTransaction();
            try {
                // Update the borrow record
                $borrow->date_return = now();
                $borrow->save();

                // Increment available copies
                $book->increment('available_copies');

                DB::commit();

                Log::info('Book returned successfully', [
                    'book_id' => $id,
                    'user_id' => $user->id,
                    'return_date' => now()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Book returned successfully',
                    'data' => [
                        'book' => $book->fresh(),
                        'borrow' => $borrow
                    ]
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error returning book', [
                    'book_id' => $id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to return book'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error in return method', [
                'book_id' => $id,
                'error' => $e->getMessage()
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
