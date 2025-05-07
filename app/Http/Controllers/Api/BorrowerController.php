<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Borrower; 
use Illuminate\Http\Request;
use App\Http\Resources\BorrowerResource;
use Illuminate\Support\Facades\Validator;

class BorrowerController extends Controller
{
    public function index()
    {
        $borrower = Borrower::all(); 
        
        if($borrower->isEmpty()) {
            return response()->json(['message' => 'No borrower found'], 200);
        }
        
        return BorrowerResource::collection($borrower);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "student_name" => "required|string",
            "block" => "required|string",
            "year_level" => "required|string",
            "book_name" => "required|string",
            "date_borrowed" => "required|date",
            "date_return" => "required|date",
        ]);

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()    
            ], 422);
        }

        $borrower = Borrower::create([
            'student_name' => $request->student_name,
            'block' => $request->block,
            'year_level' => $request->year_level,
            'book_name' => $request->book_name,
            'date_borrowed' => $request->date_borrowed,
            'date_return' => $request->date_return,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Borrower created successfully',
            'data' => new BorrowerResource($borrower)
        ], 201); // Changed to 201 for created
    }

    public function show($id)
    {
        $borrower = Borrower::find($id);
        
        if(!$borrower) {
            return response()->json([
                'success' => false,
                'message' => 'Borrower not found'
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
                'message' => 'Borrower not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            "student_name" => "required|string",
            "block" => "required|string",
            "year_level" => "required|string",
            "book_name" => "required|string",
            "date_borrowed" => "required|date",
            "date_return" => "required|date",
        ]);

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()    
            ], 422);
        }

        $borrower->update([
           'student_name' => $request->student_name,
            'block' => $request->block,
            'year_level' => $request->year_level,
            'book_name' => $request->book_name,
            'date_borrowed' => $request->date_borrowed,
            'date_return' => $request->date_return,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Borrower updated successfully',
            'data' => new BorrowerResource($borrower)
        ], 200);
    }

    public function destroy($id)
    {
        $borrower = Borrower::find($id);
        
        if(!$borrower) {
            return response()->json([
                'success' => false,
                'message' => 'Borrower not found'
            ], 404);
        }

        $borrower->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Borrower deleted successfully'
        ], 200);
    }
}