<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ], [
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'password.required' => 'Password wajib diisi'
        ]);

        $user = User::where('email', $request->email)->first();

        // Email tidak ditemukan
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak terdaftar'
            ], 404);
        }

        // Password salah
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password tidak sesuai'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ]
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'institution' => 'nullable|string|max:255',
            'major' => 'nullable|string|max:255',
            'skill_category_id' => 'nullable|exists:skill_categories,id',
            'custom_skill' => 'nullable|string'
        ]);

        if (!$request->skill_category_id && !$request->custom_skill) {
            return response()->json([
                'success' => false,
                'message' => 'Skill wajib dipilih atau diisi'
            ], 422);
        }

        if ($request->skill_category_id) {
            $skillCategoryId = $request->skill_category_id;
            $customSkill = null;
        } else {
            $skillCategoryId = null;
            $customSkill = $request->custom_skill;
        }

        $user = User::create([
            'name' => $request->first_name . ' ' . $request->last_name,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'institution' => $request->institution,
            'major' => $request->major,
            'skill_category_id' => $skillCategoryId,
            'custom_skill' => $customSkill,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Register berhasil',
            'data' => [
                'access_token' => $token,
                'user' => $user
            ]
        ], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil logout'
        ]);
    }
}