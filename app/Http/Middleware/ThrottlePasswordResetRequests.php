<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Forgot password?" form: at most a few requests per minute from one address. Fortify only limits each email
 * (one link a minute), so without this anyone could send reset emails to every staff address in a loop.
 * Changing your own password (My Profile) is limited too, so a borrowed session cannot guess the current one.
 */
class ThrottlePasswordResetRequests
{
    public function __construct(private readonly ThrottleRequests $throttle) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('post') && $request->routeIs('password.email')) {
            return $this->throttle->handle($request, $next, 'forgot-password');
        }
        if ($request->isMethod('put') && $request->routeIs('user-password.update')) {
            return $this->throttle->handle($request, $next, 'password-update');
        }

        return $next($request);
    }
}
