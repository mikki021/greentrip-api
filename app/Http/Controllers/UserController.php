<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Create a new UserController instance.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display the authenticated user's profile.
     */
    public function profile(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'status' => 'success',
            'user' => $user
        ]);
    }

    /**
     * Display the specified user (only if it's the authenticated user).
     */
    public function show(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if (auth()->user()->cannot('view', $user)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized to view this profile'
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'user' => $user
        ]);
    }

    /**
     * Update the specified user's profile (only if it's the authenticated user).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if (auth()->user()->cannot('update', $user)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized to update this profile'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'current_password' => 'required_with:password|string',
            'password' => 'sometimes|required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->has('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Current password is incorrect'
                ], 422);
            }
        }

        $user->fill($request->only(['name', 'email']));

        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Remove the specified user (only if it's the authenticated user).
     */
    public function destroy(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if (auth()->user()->cannot('delete', $user)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized to delete this account'
            ], 403);
        }

        auth()->logout();

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Account deleted successfully'
        ]);
    }
}
