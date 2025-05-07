<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student; // Changed to singular
use Illuminate\Http\Request;
use App\Http\Resources\StudentsResource;
use Illuminate\Support\Facades\Validator;

class StudentsController extends Controller
{
    public function index()
    {
        $students = Student::all(); // Changed to all()
        
        if($students->isEmpty()) {
            return response()->json(['message' => 'No students found'], 200);
        }
        
        return StudentsResource::collection($students);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "studentname" => "required|string",
            "block" => "required|string",
            "yearlevel" => "required|string",
            "email" => "required|email",
            "phone" => "required|string",
        ]);

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()    
            ], 422);
        }

        $student = Student::create([
            'studentname' => $request->studentname,
            'block' => $request->block,
            'yearlevel' => $request->yearlevel,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Student created successfully',
            'data' => new StudentsResource($student)
        ], 201); // Changed to 201 for created
    }

    public function show($id)
    {
        $student = Student::find($id);
        
        if(!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
        }
        
        return new StudentsResource($student);
    }

    public function update(Request $request, $id)
    {
        $student = Student::find($id);
        
        if(!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            "studentname" => "required|string",
            "block" => "required|string",
            "yearlevel" => "required|string",
            "email" => "required|email",
            "phone" => "required|string",
        ]);

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()    
            ], 422);
        }

        $student->update([
            'studentname' => $request->studentname,
            'block' => $request->block,
            'yearlevel' => $request->yearlevel,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Student updated successfully',
            'data' => new StudentsResource($student)
        ], 200);
    }

    public function destroy($id)
    {
        $student = Student::find($id);
        
        if(!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
        }

        $student->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Student deleted successfully'
        ], 200);
    }
}