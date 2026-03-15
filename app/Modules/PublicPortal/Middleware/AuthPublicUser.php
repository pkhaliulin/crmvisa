<?php

namespace App\Modules\PublicPortal\Middleware;

use App\Modules\PublicPortal\Services\PhoneAuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthPublicUser
{
    public function __construct(private PhoneAuthService $auth) {}

    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Требуется авторизация'], 401);
        }

        $user = $this->auth->findByToken($token);

        if (! $user) {
            return response()->json(['message' => 'Токен недействителен'], 401);
        }

        $request->merge(['_public_user' => $user]);
        $request->setUserResolver(fn () => $user);

        // RLS: разрешить публичному пользователю работу с его записями
        DB::statement("SELECT set_config('app.is_public_user', 'true', false)");

        $userId = $user->id;
        if (preg_match('/^[0-9a-f\-]{36}$/i', $userId)) {
            DB::statement("SELECT set_config('app.public_user_id', ?, false)", [$userId]);
        }

        return $next($request);
    }
}
