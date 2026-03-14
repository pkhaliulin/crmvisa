<?php

namespace App\Modules\PublicPortal\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationCode;
use App\Mail\WelcomeEmail;
use App\Support\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PublicEmailController extends Controller
{
    /**
     * POST /public/me/email
     * Сохранить recovery email + отправить код верификации.
     */
    public function saveEmail(Request $request): JsonResponse
    {
        $user = $request->get('_public_user');

        $data = $request->validate([
            'recovery_email' => 'required|email|max:255',
        ]);

        $email = strtolower($data['recovery_email']);
        $isNewEmail = $user->recovery_email !== $email;

        $user->update([
            'recovery_email'    => $email,
            'email_verified_at' => $isNewEmail ? null : $user->email_verified_at,
        ]);

        // Генерируем 4-значный код верификации
        $code = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        // Удаляем старые неиспользованные коды
        DB::table('public_email_verifications')
            ->where('public_user_id', $user->id)
            ->whereNull('verified_at')
            ->delete();

        DB::table('public_email_verifications')->insert([
            'id'             => Str::uuid()->toString(),
            'public_user_id' => $user->id,
            'email'          => $email,
            'code'           => $code,
            'expires_at'     => now()->addMinutes(15),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        try {
            Mail::to($email)->send(new EmailVerificationCode(
                code: $code,
                userName: $user->name ?? 'Пользователь',
            ));
        } catch (\Throwable $e) {
            \Log::warning('Email verification send failed', ['email' => $email, 'error' => $e->getMessage()]);
        }

        return ApiResponse::success([
            'user'              => $user->fresh(),
            'verification_sent' => true,
        ], 'Код подтверждения отправлен на email');
    }

    /**
     * POST /public/me/email/verify
     * Подтвердить email по 4-значному коду.
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $user = $request->get('_public_user');

        $data = $request->validate([
            'code' => 'required|string|size:4',
        ]);

        $record = DB::table('public_email_verifications')
            ->where('public_user_id', $user->id)
            ->where('code', $data['code'])
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $record) {
            return ApiResponse::error('Неверный или истёкший код.', null, 422);
        }

        DB::table('public_email_verifications')
            ->where('id', $record->id)
            ->update(['verified_at' => now()]);

        $user->update(['email_verified_at' => now()]);

        // Отправляем welcome-письмо после верификации
        try {
            Mail::to($user->recovery_email)->send(new WelcomeEmail(
                userName: $user->name ?? 'Пользователь',
                userPhone: $user->phone,
            ));
        } catch (\Throwable $e) {
            \Log::warning('Welcome email failed', ['error' => $e->getMessage()]);
        }

        return ApiResponse::success([
            'user' => $user->fresh(),
        ], 'Email подтверждён');
    }
}
