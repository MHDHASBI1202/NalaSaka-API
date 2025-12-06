<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Untuk aplikasi API (non-browser), ketika permintaan tidak terautentikasi,
        // kita HARUS mengembalikan NULL agar Laravel merespons dengan HTTP 401 Unauthorized.
        // Jika dibiarkan default, Laravel akan mencoba mengalihkan ke route web 'login',
        // yang menyebabkan error 'Route [login] not defined'.
        
        return $request->expectsJson() ? null : null;
    }
}