<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmailVerificationController extends Controller
{
    private AuthService $authService;
    private EmailService $emailService;

    public function __construct(AuthService $authService, EmailService $emailService)
    {
        $this->authService = $authService;
        $this->emailService = $emailService;
    }

    /**
     * Verify email with token.
     */
    public function verify(string $token): JsonResponse
    {
        $user = $this->emailService->verifyEmail($token);

        if (!$user) {
            return response()->json([
                'message' => 'Invalid or expired verification token.',
            ], 400);
        }

        return response()->json([
            'message' => 'Email verified successfully. You can now log in.',
            'user' => $user,
        ]);
    }

    /**
     * Resend verification email.
     */
    public function resend(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $result = $this->authService->resendVerificationEmail($request->email);

            return response()->json($result);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }
}
