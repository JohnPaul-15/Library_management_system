<?php

namespace App\Http\Controllers\Api;

use App\Models\Book;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\BookResource;
use Illuminate\Support\Facades\Validator;

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
}
