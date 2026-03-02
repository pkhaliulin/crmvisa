<?php

namespace App\Modules\Auth\Services;

use App\Modules\Agency\DTOs\RegisterAgencyDTO;
use App\Modules\Agency\Services\AgencyService;
use App\Modules\Auth\DTOs\LoginDTO;
use App\Modules\Auth\DTOs\RegisterDTO;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function __construct(protected AgencyService $agencyService) {}

    public function register(RegisterDTO $dto): array
    {
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

        return $this->tokenResponse($token, $user);
    }

    public function login(LoginDTO $dto): array
    {
        // DEBUG: find user and check hash manually
        $dbUser = \App\Modules\User\Models\User::where('email', $dto->email)->first();
        \Log::error('LOGIN_DEBUG', [
            'user_found'    => $dbUser ? true : false,
            'hash_prefix'   => $dbUser ? substr($dbUser->password, 0, 15) : null,
            'hash_strlen'   => $dbUser ? strlen($dbUser->password) : null,
            'hash_hex_end'  => $dbUser ? bin2hex(substr($dbUser->password, -3)) : null,
            'native_verify' => $dbUser ? password_verify($dto->password, $dbUser->password) : null,
            'hardcoded_verify' => $dbUser ? password_verify('Owner@2026!', '$2y$12$KB3ofgnJR5Ht.0DVnIVh6uJuLykuHr6DRop4WyeRvj6fCyIQ2ETe2') : null,
        ]);

        $token = JWTAuth::attempt([
            'email'    => $dto->email,
            'password' => $dto->password,
        ]);

        \Log::error('JWT_ATTEMPT_RESULT', ['token_bool' => (bool) $token]);

        if (! $token) {
            throw ValidationException::withMessages([
                'email' => 'Invalid credentials.',
            ]);
        }

        $user = JWTAuth::user();

        if (! $user->is_active) {
            JWTAuth::invalidate();
            throw ValidationException::withMessages([
                'email' => 'Account is deactivated.',
            ]);
        }

        return $this->tokenResponse($token, $user);
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
