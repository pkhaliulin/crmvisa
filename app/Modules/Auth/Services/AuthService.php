<?php

namespace App\Modules\Auth\Services;

use App\Modules\Agency\DTOs\RegisterAgencyDTO;
use App\Modules\Agency\Services\AgencyService;
use App\Modules\Auth\DTOs\LoginDTO;
use App\Modules\Auth\DTOs\RegisterDTO;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function __construct(protected AgencyService $agencyService) {}

    public function register(RegisterDTO $dto): array
    {
        // RLS bypass: при регистрации пользователь ещё не авторизован
        return DB::transaction(function () use ($dto) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                DB::statement("SET LOCAL app.is_superadmin = 'true'");
            }

            $agencyDTO = RegisterAgencyDTO::fromArray([
                'name'     => $dto->agencyName,
                'email'    => $dto->email,
                'phone'    => $dto->phone,
                'country'  => $dto->country,
                'timezone' => $dto->timezone,
            ]);

            $agency = $this->agencyService->register($agencyDTO);

            $user = User::create([
                'agency_id' => $agency->id,
                'name'      => $dto->ownerName,
                'email'     => $dto->email,
                'password'  => Hash::make($dto->password),
                'role'      => 'owner',
                'is_active' => true,
            ]);

            $token = JWTAuth::fromUser($user);

            // Отправляем письмо верификации email
            $this->sendVerificationEmail($user);

            return $this->tokenResponse($token, $user);
        });
    }

    public function sendVerificationEmail(User $user): void
    {
        try {
            $verifyUrl = URL::temporarySignedRoute(
                'auth.verify-email',
                now()->addHours(24),
                ['id' => $user->id],
            );

            Mail::send('emails.crm-verify-email', [
                'userName'  => $user->name,
                'verifyUrl' => $verifyUrl,
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('VisaBor — Подтвердите ваш email');
            });
        } catch (\Throwable $e) {
            \Log::warning('Failed to send verification email', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    public function login(LoginDTO $dto): array
    {
        // RLS bypass: при логине пользователь ещё не авторизован,
        // SET LOCAL действует только внутри этой транзакции
        return DB::transaction(function () use ($dto) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                DB::statement("SET LOCAL app.is_superadmin = 'true'");
            }

            $user = User::where('email', $dto->email)->first();

            if (! $user) {
                \Log::channel('auth')->warning('Login failed: user not found', ['email' => $dto->email]);
                throw ValidationException::withMessages([
                    'email' => __('auth.user_not_found'),
                ]);
            }

            if (! $user->is_active) {
                \Log::channel('auth')->warning('Login failed: account deactivated', ['email' => $dto->email, 'user_id' => $user->id]);
                throw ValidationException::withMessages([
                    'email' => __('auth.account_deactivated'),
                ]);
            }

            $token = JWTAuth::attempt([
                'email'    => $dto->email,
                'password' => $dto->password,
            ]);

            if (! $token) {
                \Log::channel('auth')->warning('Login failed: wrong password', ['email' => $dto->email, 'user_id' => $user->id]);
                throw ValidationException::withMessages([
                    'password' => __('auth.wrong_password'),
                ]);
            }

            \Log::channel('auth')->info('Login success', ['email' => $user->email, 'user_id' => $user->id, 'role' => $user->role]);

            \App\Support\Helpers\AuditLog::log('auth.crm_login', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'agency_id' => $user->agency_id,
            ]);

            return $this->tokenResponse($token, $user);
        });
    }

    public function logout(): void
    {
        JWTAuth::invalidate(JWTAuth::getToken());
    }

    public function refresh(): array
    {
        $token = JWTAuth::refresh(JWTAuth::getToken());
        $user  = JWTAuth::setToken($token)->toUser();

        return $this->tokenResponse($token, $user);
    }

    public function me(): User
    {
        return JWTAuth::user();
    }

    // -------------------------------------------------------------------------

    private function tokenResponse(string $token, User $user): array
    {
        return [
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => config('jwt.ttl') * 60,
            'user'         => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->role,
                'agency_id'  => $user->agency_id,
            ],
        ];
    }
}
