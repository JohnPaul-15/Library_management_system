<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    // List all books - browsing
    public function index()
    {
        $books = Book::all();
        return BookResource::collection($books);
    }

    // Admin can create book
    public function store(Request $request)
    {
        $this->authorize('adminOnly');

        $validated = $request->validate([
            'title' => 'required|string',
            'author' => 'required|string',
            'publisher' => 'required|string',
            'copies' => 'required|integer|min:1',
        ]);

        $validated['available_copies'] = $validated['copies'];

        $book = Book::create($validated);

        return new BookResource($book);
    }

    // Admin can update book details
    public function update(Request $request, Book $book)
    {
        $this->authorize('adminOnly');

        $validated = $request->validate([
            'title' => 'sometimes|string',
            'author' => 'sometimes|string',
            'publisher' => 'sometimes|string',
            'copies' => 'sometimes|integer|min:1',
        ]);

        if (isset($validated['copies'])) {
            $difference = $validated['copies'] - $book->copies;
            $book->available_copies += $difference;
        }
        

        $book->update($validated);

        return new BookResource($book);
    }

    // Admin can delete book
    public function destroy(Book $book)
    {
        $this->authorize('adminOnly');

        $book->delete();

        return response()->json(['message' => 'Book deleted']);
    }
    // In BookController for admin-only routes
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin')->only(['store', 'update', 'destroy']);
    }

    
}
