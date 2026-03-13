<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class UserController extends Controller
{
    public function index(){
        return response()->json(User::paginate(25));
    }

    public function store(Request $request){
        if ($request->has('role')) {
            $request->merge([
                'role' => strtolower(trim($request->role))
            ]);
        }

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8', //*JUST AS DEFAULT, I'D CHANGE TO SOMETHING MORE SECURE IN PRODUCTION
            'role'     => ['required', Rule::in(['admin', 'manager', 'finance', 'user'])],
        ]);

        //MAKE SURE NOT TO STORE THE PASSWORDS IN PLAIN TEXT AAAND LOWER CASE THE ROLE
        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => $validated['role'],
        ]);

        Log::info('User ' . $user->email . ' created', ['id' => $user->id, 'role' => $user->role]);
        return response()->json($user, 201);
    }

    public function show(User $user){
        return response()->json($user);
    }

    public function update(Request $request, User $user){
        $validated = $request->validate([
            'name'     => 'string|max:255',
            'email'    => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role'     => ['sometimes', Rule::in(['admin', 'manager', 'finance', 'user'])],
        ]);

        //IF PASSWORD IS NULL OR NOT PRESENT, DON'T UPDATE IT
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']); // Don't overwrite with null
        }

        //LOWERCASE ROLE IF PRESENT
        if (isset($validated['role'])) {
            $validated['role'] = strtolower($validated['role']);
        }

        $user->update($validated);


        Log::info('User ' . $user->email . ' updated', ['id' => $user->id, 'role' => $user->role]);
        return response()->json($user);
    }

    public function destroy(User $user){
        $user->delete();

        Log::info('User ' . $user->email . ' deleted', ['id' => $user->id]);
        return response()->json(['message' => 'User deleted successfully'], 204);
    }
}
