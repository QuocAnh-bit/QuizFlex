<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email hoặc mật khẩu không đúng',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'data' => $this->formatUser($user),
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
            'role' => ['nullable', Rule::in(['USER', 'VIP', 'user', 'vip'])],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => strtoupper($data['role'] ?? 'USER'),
            'ai_quota_remaining' => strtoupper($data['role'] ?? 'USER') === 'VIP' ? 500 : 5,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo tài khoản thành công',
            'data' => $this->formatUser($user),
        ], 201);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => strtolower($user->role ?? 'user'),
            'role_label' => match (strtoupper($user->role ?? 'USER')) {
                'ADMIN' => 'Admin',
                'VIP' => 'VIP',
                default => 'Thường',
            },
            'ai_quota_remaining' => $user->ai_quota_remaining,
            'vip_expires_at' => $user->vip_expires_at,
            'created_at' => $user->created_at,
        ];
    }
}
