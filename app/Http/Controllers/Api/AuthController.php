<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Wallet;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'role' => ['sometimes', new Enum(UserRole::class)],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => $request->role ?? UserRole::TOURIST,
            'status' => UserStatus::ACTIVE,
        ]);

        // Buat wallet otomatis untuk semua user
        Wallet::create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        return response()->json([
            'message' => 'Registrasi berhasil',
            'user' => new UserResource($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang diberikan tidak cocok.'],
            ]);
        }

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login berhasil',
            'user' => new UserResource(Auth::user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logout berhasil',
        ]);
    }

    public function me(Request $request): JsonResponse
{
    // TAMBAHKAN ->load('wallet') DI SINI
    $user = $request->user()->load('wallet');
    
    return response()->json([
        'user' => new UserResource($user),
    ]);
}

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
        ]);

        $user->update($request->only('name', 'phone'));

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user' => new UserResource($user->fresh()),
        ]);
    }

    
}