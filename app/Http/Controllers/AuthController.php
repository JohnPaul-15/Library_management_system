<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    //Register Api(name, email, password, confirm password) 
    public function register(Request $request){
        $data = $request->validate([
            "name" => 'required|string|max:255',
            "email" => 'required|string|email|max:255|unique:users',
            "password" => 'required|string|min:8|confirmed',
        ]);

        User::create($data);

        return response()->json([
            "status" => true,
            "message" => 'User registered successfully',
        ], 201);
    }

    //Login Api(email, password)
    public function login (Request $request){
        
        $request->validate([
        "email" => "required|string|email",
        "password" => "required|string",
        ]);

        if(!Auth::attemp($request->only('email', 'password'))) {
            
            return response()->json([
            "status" => false,
            "message" => 'Invalid credentials',
        ]);
    }
    $user = Auth::user();
    $token = $user->createToken("myToken")->plainTextToken;

    return response()->json([
        "status" => true,
        "message" => 'User logged in successfully',
        "token" => $token,
    ]);
    }

    //Profile Api
    public function profile() {

        $user = Auth::user();

        return response()->json([
            "status" => true,
            "message" => 'User profile',
            "data" => $user,
        ]);
    }
    
    //Logout Api
    public function logout() {

        Auth::logout();

            return response()->json([
                "status" => true,
                "message" => 'User logged out successfully',
            ]);
        }
}
