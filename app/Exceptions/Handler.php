<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request; // Importar Request
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
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Esta sección es crucial para manejar errores de API como JSON
        $this->renderable(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                // Para errores de validación, devuelve un 422
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $e->errors(),
                    ], 422);
                }

                // Para otros errores, devuelve un 500 con el mensaje de error
                return response()->json([
                    'message' => $e->getMessage(),
                    // Opcionalmente, si APP_DEBUG es true, puedes incluir el stack trace para depuración
                    // 'trace' => config('app.debug') ? $e->getTrace() : null,
                ], 500);
            }
        });
    }
}