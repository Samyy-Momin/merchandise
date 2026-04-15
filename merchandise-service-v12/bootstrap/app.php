<?php

use App\Exceptions\AcknowledgementNotFoundException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidAcknowledgementStateException;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\InvoiceNotFoundException;
use App\Exceptions\OrderNotFoundException;
use App\Exceptions\SkuHasActiveOrdersException;
use App\Exceptions\SkuNotFoundException;
use App\Exceptions\UnauthorizedOrderCancellationException;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\ResolveTenantContext;
use CubeOneBiz\Tenant\Middleware\ValidateKeycloakJwt;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'request.context' => \CubeOneBiz\Chassis\Http\Middleware\RequestContextMiddleware::class,
            'role' => CheckRole::class,
            'permission' => CheckPermission::class,
            'keycloak.jwt' => ValidateKeycloakJwt::class,
            'tenant' => ResolveTenantContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (SkuNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        });
        $exceptions->render(function (OrderNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        });
        $exceptions->render(function (InvoiceNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        });
        $exceptions->render(function (AcknowledgementNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        });
        $exceptions->render(function (InsufficientStockException $e) {
            return response()->json(['message' => 'Insufficient stock', 'detail' => $e->getMessage()], 422);
        });
        $exceptions->render(function (InvalidOrderTransitionException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });
        $exceptions->render(function (InvalidAcknowledgementStateException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });
        $exceptions->render(function (SkuHasActiveOrdersException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        });
        $exceptions->render(function (UnauthorizedOrderCancellationException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        });
    })->create();
