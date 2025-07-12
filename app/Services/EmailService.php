<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EmailService
{
    /**
     * Send email verification to user.
     */
    public function sendVerificationEmail(User $user): bool
    {
        try {
            // Generate verification token
            $token = Str::random(64);
            $expiresAt = Carbon::now()->addHours(48);

            // Update user with verification token
            $user->update([
                'email_verification_token' => $token,
                'email_verification_expires_at' => $expiresAt,
            ]);

            // Send verification email
            Mail::send('emails.verify', [
                'user' => $user,
                'token' => $token,
                'expiresAt' => $expiresAt,
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Verify Your Email Address');
            });

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Verify email with token.
     */
    public function verifyEmail(string $token): ?User
    {
        $user = User::where('email_verification_token', $token)
            ->where('email_verification_expires_at', '>', Carbon::now())
            ->first();

        if ($user) {
            $user->update([
                'email_verified_at' => Carbon::now(),
                'email_verification_token' => null,
                'email_verification_expires_at' => null,
            ]);

            return $user;
        }

        return null;
    }

    /**
     * Resend verification email.
     */
    public function resendVerificationEmail(User $user): bool
    {
        // Clear any existing expired tokens
        if ($user->isEmailVerificationExpired()) {
            $user->update([
                'email_verification_token' => null,
                'email_verification_expires_at' => null,
            ]);
        }

        return $this->sendVerificationEmail($user);
    }
}