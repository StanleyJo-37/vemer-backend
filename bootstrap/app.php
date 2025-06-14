<?php

use App\Http\Middleware\AcceptJson;
use App\Http\Middleware\AddCors;
use App\Http\Middleware\AddToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/v1/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
        $middleware->append(AddCors::class);
        $middleware->append(AcceptJson::class);
        $middleware->append(AddToken::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
