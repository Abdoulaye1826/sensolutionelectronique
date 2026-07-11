<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
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
    }

    /**
     * Rend une session expirée (419) explicite plutôt que la page d'erreur
     * brute de Laravel : redirection vers la connexion avec un message pour
     * les requêtes classiques, réponse JSON pour les requêtes AJAX (le ping
     * de session-keepalive.js s'en sert pour rediriger côté client).
     */
    public function render($request, Throwable $e)
    {
        if ($e instanceof TokenMismatchException) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Votre session a expiré. Veuillez vous reconnecter.',
                ], 419);
            }

            return redirect()->guest(route('login', ['expired' => 1]));
        }

        return parent::render($request, $e);
    }
}
