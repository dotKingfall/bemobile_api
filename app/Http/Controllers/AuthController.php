<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        Log::info('Login attempt initiated', ['email' => $request->email]);

        $request->validate([
            'email' => 'required|email', //USE email:rfc,dns ON REAL PRODUCTION
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            Log::warning('Login failed: Invalid credentials', ['email' => $request->email]);
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        Log::info('Login successful', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role
        ]);

        return response()->json([
            'token' => $user->createToken('auth_token')->plainTextToken,
            'role' => strtolower($user->role),
        ], 200);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        Log::info('Logout attempt initiated', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role
        ]);

        $user->currentAccessToken()->delete();

        Log::info('Logout successful');

        return response()->json(['message' => 'Logged out successfully'], 200);
    }
}
