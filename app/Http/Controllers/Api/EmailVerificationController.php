<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification link.',
            ], 404);
        }

        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification link.',
            ], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Email already verified.',
            ]);
        }

        $user->forceFill(['email_verified_at' => now()])->save();

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully.',
        ]);
    }

    public function sendNotification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Email already verified.',
            ]);
        }

        try {
            Mail::to($user->email)->queue(new VerifyEmailMail($user));
        } catch (\Exception $e) {
            \Log::error('Failed to send verification email: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Could not send verification email. Please try again.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification email sent.',
        ]);
    }
}
