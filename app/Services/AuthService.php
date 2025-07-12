<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register a new user.
     *
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function register(array $data): array
    {
        $this->validateRegistrationData($data);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $emailService = new EmailService();
        $emailService->sendVerificationEmail($user);

        return [
            'user' => $user,
            'message' => 'Registration successful. Please check your email to verify your account.',
        ];
    }

    /**
     * Authenticate user and get JWT token.
     *
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public function login(array $data): array
    {
        $this->validateLoginData($data);

        $credentials = [
            'email' => $data['email'],
            'password' => $data['password'],
        ];

        if (!$token = Auth::attempt($credentials)) {
            $validator = Validator::make([], []);
            $validator->errors()->add('credentials', 'Invalid email or password.');
            throw new ValidationException($validator);
        }

        $user = Auth::user();

        if (!$user->isEmailVerified()) {
            Auth::logout();
            $validator = Validator::make([], []);
            $validator->errors()->add('email', 'Please verify your email address before logging in.');
            throw new ValidationException($validator);
        }

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Logout the authenticated user.
     *
     * @return bool
     */
    public function logout(): bool
    {
        Auth::logout();
        return true;
    }

    /**
     * Get the authenticated user.
     *
     * @return User|null
     */
    public function getCurrentUser(): ?User
    {
        return Auth::user();
    }

    /**
     * Refresh the JWT token.
     *
     * @return array
     */
    public function refreshToken(): array
    {
        $token = Auth::refresh();
        return [
            'user' => Auth::user(),
            'token' => $token,
        ];
    }

    /**
     * Resend verification email.
     *
     * @param string $email
     * @return array
     * @throws ValidationException
     */
    public function resendVerificationEmail(string $email): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            $validator = Validator::make([], []);
            $validator->errors()->add('email', 'User not found with this email address.');
            throw new ValidationException($validator);
        }

        if ($user->isEmailVerified()) {
            $validator = Validator::make([], []);
            $validator->errors()->add('email', 'Email is already verified.');
            throw new ValidationException($validator);
        }

        $emailService = new EmailService();
        $success = $emailService->resendVerificationEmail($user);

        if (!$success) {
            $validator = Validator::make([], []);
            $validator->errors()->add('email', 'Failed to send verification email. Please try again.');
            throw new ValidationException($validator);
        }

        return [
            'message' => 'Verification email sent successfully.',
        ];
    }

    /**
     * Validate registration data.
     *
     * @param array $data
     * @throws ValidationException
     */
    private function validateRegistrationData(array $data): void
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Validate login data.
     *
     * @param array $data
     * @throws ValidationException
     */
    private function validateLoginData(array $data): void
    {
        $validator = Validator::make($data, [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}