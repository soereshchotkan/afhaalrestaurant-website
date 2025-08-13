<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    /**
     * Send password reset link
     */
    public function sendResetLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email adres niet gevonden',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        // Genereer reset token
        $token = Str::random(64);
        
        // Verwijder oude tokens voor deze gebruiker
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        
        // Sla nieuwe token op
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => Hash::make($token),
            'created_at' => Carbon::now()
        ]);

        // Stuur email (simplified version - in productie gebruik Mail facade)
        // Voor nu retourneren we de token voor testing
        // In productie: Mail::to($user->email)->send(new PasswordResetMail($token));
        
        // Frontend URL waar gebruiker naartoe gaat met token
        $resetUrl = config('app.frontend_url', 'http://localhost:3000') . '/reset-password?token=' . $token . '&email=' . urlencode($request->email);

        return response()->json([
            'success' => true,
            'message' => 'Wachtwoord reset link is verzonden naar je email',
            // Alleen voor development/testing - verwijder in productie!
            'debug' => [
                'token' => $token,
                'reset_url' => $resetUrl
            ]
        ], 200);
    }

    /**
     * Reset password with token
     */
    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validatie mislukt',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check token
        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$passwordReset) {
            return response()->json([
                'success' => false,
                'message' => 'Ongeldige reset token'
            ], 400);
        }

        // Verify token
        if (!Hash::check($request->token, $passwordReset->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Ongeldige reset token'
            ], 400);
        }

        // Check if token is expired (24 hours)
        if (Carbon::parse($passwordReset->created_at)->addHours(24)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'Reset token is verlopen'
            ], 400);
        }

        // Update password
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete reset token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Optioneel: logout van alle apparaten
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Wachtwoord succesvol gereset'
        ], 200);
    }

    /**
     * Verify if reset token is valid
     */
    public function verifyToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validatie mislukt',
                'errors' => $validator->errors()
            ], 422);
        }

        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$passwordReset || !Hash::check($request->token, $passwordReset->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Ongeldige token'
            ], 400);
        }

        // Check expiration
        if (Carbon::parse($passwordReset->created_at)->addHours(24)->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Token is verlopen'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Token is geldig'
        ], 200);
    }
}