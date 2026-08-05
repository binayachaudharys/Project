<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $allowed = collect($roles)
            ->flatMap(fn (string $r) => explode(',', $r))
            ->map(fn (string $r) => trim($r))
            ->filter()
            ->all();

        $roleValue = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        if (! in_array($roleValue, $allowed, true)) {
            abort(403);
        }

        return $next($request);
    }
}
