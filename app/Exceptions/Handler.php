<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException; // <-- Import ini!
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Convert an authentication exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // Jika permintaan mengharapkan JSON (misalnya dari API),
        // atau jika URI-nya diawali dengan 'api/', kita kembalikan JSON 401.
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['error' => true, 'message' => 'Unauthenticated.'], 401);
        }
        
        // Jika tidak, kita biarkan perilaku default (mengalihkan ke 'login' untuk aplikasi web), 
        // meskipun ini tidak digunakan di API Anda.
        return redirect()->guest(route('login'));
    }

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}