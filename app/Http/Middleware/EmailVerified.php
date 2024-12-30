<?php

namespace App\Http\Middleware;

use App\Enums\SettingsKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Exceptions\EmailNotVerifiedException;

class EmailVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(get_setting(SettingsKey::ENABLE_EMAIL_VERIFICATION->name, false)){
            if (!$request->user()->isEmailVerified()) {
                throw new EmailNotVerifiedException();
            }
        }

        return $next($request);
    }
}
