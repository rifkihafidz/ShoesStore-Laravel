<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Rules\Password;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function register(Request $request) {
        try {
            $request->validate([
                'name' => ['required','string','max:255'],
                'email' => ['required','string','max:255','email','unique:users'],
                'username' => ['required','string','max:255','unique:users'],
                // 'phone' => ['nullable','string','max:255'],
                'password' => ['required','string', new Password],
            ]);

            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                // 'phone' => $request->phone,
                'password' => Hash::make($request->password),
            ]);

            $user = User::where('email', $request->email)->first();

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user,
            ], 'User successfully registered.');
        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong.',
                'error' => $error,
            ], 'Authentication failed.', 500);
        }
    }

    public function login(Request $request) {
        try {
            $request->validate([
                'email' => 'email|required',
                'password' => 'required',
            ]);

            $credentials = request(['email','password']);

            if(!Auth::attempt($credentials)) {
                return ResponseFormatter::error([
                    'message' => 'Unauthorized.',
                ], 'Authentication failed.', 500);
            }

            $user = User::where('email', $request->email)->first();

            // Cek apakah password yang di input dengan password di database sama / tidak
            if(!Hash::check($request->password, $user->password, [])) {
                throw new \Exception('Invalid credentials.');
            }

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user,
            ], 'Authenticated');

        } catch (Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something went wrong.',
                'error' => $error,
            ], 'Authentication failed.', 500);
        }
    }

    public function fetch(Request $request) {
        return ResponseFormatter::success($request->user(), 'User data successfully fetched.');
    }

    public function updateProfile(Request $request) {
        $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','string','max:255','email'],
            'username' => ['required','string','max:255'],
            'phone' => ['nullable','string','max:255'],
        ]);

        $data = $request->all();

        $user = Auth::user();

        $user->update($data);

        return ResponseFormatter::success($user, 'Profile successfully updated.');
    }

    public function logout(Request $request) {
        $token = $request->user()->currentAccessToken()->delete();

        return ResponseFormatter::success($token, 'Token successfully revoked.');
    }
}
