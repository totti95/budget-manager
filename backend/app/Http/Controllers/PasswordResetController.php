<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)
            ->whereNull('deleted_at')
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Cet utilisateur n\'existe pas ou a été désactivé.'],
            ]);
        }

        $token = Str::random(60);

        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $resetUrl = env('FRONTEND_URL', 'http://localhost:5173').'/reset-password?token='.$token.'&email='.urlencode($request->email);

        try {
            Mail::send('emails.password-reset', [
                'user' => $user,
                'resetUrl' => $resetUrl,
            ], function ($message) use ($request) {
                $message->to($request->email)
                    ->subject('Réinitialisation de votre mot de passe - Budget Manager');
            });
        } catch (\Exception $e) {
            \Log::warning('Failed to send password reset email: '.$e->getMessage());
        }

        return response()->json([
            'message' => 'Un email de réinitialisation a été envoyé à votre adresse.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $passwordReset) {
            throw ValidationException::withMessages([
                'email' => ['Ce lien de réinitialisation est invalide ou a expiré.'],
            ]);
        }

        if (now()->diffInMinutes($passwordReset->created_at) > 60) {
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            throw ValidationException::withMessages([
                'token' => ['Ce lien de réinitialisation a expiré. Veuillez en demander un nouveau.'],
            ]);
        }

        if (! Hash::check($request->token, $passwordReset->token)) {
            throw ValidationException::withMessages([
                'token' => ['Ce lien de réinitialisation est invalide.'],
            ]);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['Utilisateur introuvable.'],
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->delete();

        return response()->json([
            'message' => 'Votre mot de passe a été réinitialisé avec succès.',
        ]);
    }
}
