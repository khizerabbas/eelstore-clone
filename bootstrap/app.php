<?php

use App\Support\ErrorRecorder;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Only admins have accounts, so guests hitting an auth-protected route go to the admin login
        $middleware->redirectGuestsTo(fn () => route('filament.admin.auth.login'));

        // Browser security headers on every page (storefront and admin)
        $middleware->web(append: [\App\Http\Middleware\SecurityHeaders::class]);

        // Browser error reports must arrive even when the page's CSRF token has expired
        $middleware->validateCsrfTokens(except: ['client-errors']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Save crashes to the database for the admin (Settings → Error log). laravel.log keeps them too.
        $exceptions->report(function (Throwable $e) {
            ErrorRecorder::exception($e, ErrorRecorder::isHttp() ? 'server' : 'console');
        });

        // Customer-facing problems that aren't crashes: page expired (419), rate limited (429), optionally 404
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();

            // (the error-report endpoint itself is skipped so a flood of reports can't flood the log)
            if (in_array($status, config('errorlog.http_statuses', []), true) && ! $request->is('client-errors')) {
                ErrorRecorder::http($status, match ($status) {
                    419     => 'Page expired: the form was open too long or the session ended (visitor saw "Page Expired")',
                    429     => 'Too many requests: a visitor hit a rate limit',
                    404     => 'Page not found',
                    default => 'HTTP ' . $status . ' response',
                }, ['exception' => class_basename($e)]);
            }

            return $response;
        });
    })->create();
