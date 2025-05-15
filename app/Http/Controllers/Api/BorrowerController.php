<?php

namespace App\Http\Controllers;

use App\Http\Resources\BorrowerResource;
use App\Models\Book;
use App\Models\Borrower;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BorrowController extends Controller
{
    // Borrow a book
    public function borrow(Request $request, $bookId)
    {
        $user = Auth::user();

        $book = Book::findOrFail($bookId);

        if ($book->available_copies < 1) {
            return response()->json(['message' => 'No available copies'], 400);
        }

        $dateBorrowed = Carbon::now();
        $dueDate = $dateBorrowed->copy()->addDays(14); // 2 weeks due date

        $borrow = Borrower::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'date_borrowed' => $dateBorrowed,
            'due_date' => $dueDate,
            'date_return' => null,
        ]);

        $book->decrement('available_copies');

        return new BorrowerResource($borrow);
    }

    // Return a book
    public function returnBook(Request $request, $borrowId)
    {
        $user = Auth::user();

        $borrow = Borrower::where('id', $borrowId)->where('user_id', $user->id)->firstOrFail();

        if ($borrow->date_return) {
            return response()->json(['message' => 'Book already returned'], 400);
        }

        $borrow->date_return = Carbon::now();
        $borrow->save();

        $book = $borrow->book;
        $book->increment('available_copies');

        return new BorrowerResource($borrow);
    }

    // View user's borrow history
    public function myBorrows()
    {
        $user = Auth::user();
        $borrows = $user->borrowings()->with('book')->get();
        return BorrowerResource::collection($borrows);
    }
}
