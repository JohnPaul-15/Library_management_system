<?php

namespace App\Http\Controllers\Api;

use App\Models\Book;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\BookResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Borrower;

class BookController extends Controller
{
    public function index()
    {
        $books = Book::get();
        if($books)
        {
            return BookResource::collection($books);
        }
        else 
        {
            return response()->json(['message' => 'No books found'], 200);
        }
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            "title" => "required",
            "author" => "required",
            "publisher" => "required",
        ]);
        if($validator->fails()){
            return response()->json([
            'message' =>'All fields are mandatory',
            'error' => $validator->errors(),    
            ],422);
        }
        $request->validate([
            'title' => 'required',
            'author' => 'required',
            'publisher' => 'required',
        ]);

        $books = Book::create([
            'title' => $request->title,
            'author' => $request->author,
            'publisher' => $request->publisher,
        ]);

        return response()->json([
            'message' => 'Book created successfully',
            'data' => new BookResource($books)
        ], 200);
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
        $validator = Validator::make($request->all(),[
            "title" => "required",
            "author" => "required",
            "publisher" => "required",
        ]);
        if($validator->fails()){
            return response()->json([
            'message' =>'All fields are mandatory',
            'error' => $validator->errors(),    
            ],422);
        }
        $book->update([
            'title' => 'required',
            'author' => 'required',
            'publisher' => 'required',
        ]);

        $books = Book::create([
            'title' => $request->title,
            'author' => $request->author,
            'publisher' => $request->publisher,
        ]);

        return response()->json([
            'message' => 'Book updated successfully',
            'data' => new BookResource($books)
        ], 200);
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

        // Always return success with data array, even if empty
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
        ], 200); // Always return 200 status code
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
                'message' => 'Book is not available for borrowing'
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

        // Create borrow record
        $borrower = Borrower::create([
            'user_id' => $userId,
            'book_id' => $book->id,
            'date_borrowed' => now(),
            'due_date' => now()->addDays(14), // 2 weeks borrowing period
        ]);

        // Update book available copies
        $book->decrement('available_copies');

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

        // Update borrow record
        $borrower->update([
            'date_return' => now()
        ]);

        // Update book available copies
        $book->increment('available_copies');

        return response()->json([
            'success' => true,
            'message' => 'Book returned successfully'
        ]);
    }
}
