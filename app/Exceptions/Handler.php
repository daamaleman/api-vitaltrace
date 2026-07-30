<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpFoundation\Response;
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
     * Return an API response instead of redirecting unauthenticated API clients.
     */
    protected function unauthenticated($request, AuthenticationException $exception): Response
    {
        if ($request->is("api/*")) {
            return response()->json([
                "data" => null,
                "message" => "Unauthenticated.",
                "errors" => null,
            ], Response::HTTP_UNAUTHORIZED);
        }

        return parent::unauthenticated($request, $exception);
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
