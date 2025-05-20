<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Borrower;
use App\Models\Book;
use Illuminate\Http\Request;
use App\Http\Resources\BorrowerResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BorrowerController extends Controller
{
    public function index()
    {
        $borrower = Borrower::with(['user', 'book'])->get();
        
        if($borrower->isEmpty()) {
            return response()->json(['message' => 'No borrower found'], 200);
        }
        
        return BorrowerResource::collection($borrower);
    }

    public function store(Request $request)
    {
        // Debug authentication
        Log::info('Auth check:', [
            'isAuthenticated' => Auth::check(),
            'user' => Auth::user(),
            'userId' => Auth::id(),
            'token' => $request->bearerToken()
        ]);

        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            "book_id" => "required|exists:books,id",
            "date_borrowed" => "required|date",
            "due_date" => "required|date|after:date_borrowed",
        ]);

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()    
            ], 422);
        }

        // Check if book is available
        $book = Book::findOrFail($request->book_id);
        if ($book->available_copies <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Book is not available for borrowing'
            ], 422);
        }

        $userId = Auth::id();
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Could not determine user ID'
            ], 401);
        }

        $borrower = Borrower::create([
            'user_id' => $userId,
            'book_id' => $request->book_id,
            'date_borrowed' => $request->date_borrowed,
            'due_date' => $request->due_date,
        ]);

        // Update book available copies
        $book->decrement('available_copies');

        return response()->json([
            'success' => true,
            'message' => 'Book borrowed successfully',
            'data' => new BorrowerResource($borrower)
        ], 201);
    }

    public function show($id)
    {
        $borrower = Borrower::with(['user', 'book'])->find($id);
        
        if(!$borrower) {
            return response()->json([
                'success' => false,
                'message' => 'Borrower record not found'
            ], 404);
        }
        
        return new BorrowerResource($borrower);
    }

    public function update(Request $request, $id)
    {
        $borrower = Borrower::find($id);
        
        if(!$borrower) {
            return response()->json([
                'success' => false,
                'message' => 'Borrower record not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            "date_return" => "required|date|after_or_equal:date_borrowed",
        ]);

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()    
            ], 422);
        }

        // Only allow updating the return date
        $borrower->update([
            'date_return' => $request->date_return,
        ]);

        // If book is being returned, increment available copies
        if ($request->date_return) {
            $book = Book::find($borrower->book_id);
            $book->increment('available_copies');
        }

        return response()->json([
            'success' => true,
            'message' => 'Book return recorded successfully',
            'data' => new BorrowerResource($borrower)
        ], 200);
    }

    public function destroy($id)
    {
        $borrower = Borrower::find($id);
        
        if(!$borrower) {
            return response()->json([
                'success' => false,
                'message' => 'Borrower record not found'
            ], 404);
        }

        // If book hasn't been returned, increment available copies
        if (!$borrower->date_return) {
            $book = Book::find($borrower->book_id);
            $book->increment('available_copies');
        }

        $borrower->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Borrower record deleted successfully'
        ], 200);
    }
}