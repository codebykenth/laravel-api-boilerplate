<?php

namespace App\Exceptions;

use App\Traits\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

class ApiExceptionHandler
{
    use ApiResponse;

    public function handle(Throwable $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return $this->errorResponse('Validation Error', 422, $e->errors());
        }

        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            $message = config('app.debug') && ! app()->isProduction()
                ? ($e->getMessage() ?: 'Resource Not Found')
                : 'Resource Not Found';

            return $this->errorResponse($message, 404);
        }

        if ($e instanceof AuthorizationException) {
            return $this->errorResponse('Forbidden', 403);
        }

        if ($e instanceof AuthenticationException) {
            return $this->errorResponse($e->getMessage() ?: 'Unauthenticated', 401);
        }

        if ($e instanceof MethodNotAllowedHttpException || $e instanceof MethodNotAllowedException) {
            return $this->errorResponse('Method Not Allowed', 405);
        }

        if ($e instanceof RouteNotFoundException) {
            return $this->errorResponse('Route Not Found', 404);
        }

        if ($e instanceof TooManyRequestsHttpException) {
            return $this->errorResponse($e->getMessage() ?: 'Rate limit exceeded. Try again later.', 429);
        }

        if ($e instanceof HttpException) {
            $message = $e->getStatusCode() >= 500 && app()->isProduction()
                ? 'Server Error'
                : ($e->getMessage() ?: 'An error occurred');

            return $this->errorResponse($message, $e->getStatusCode());
        }

        if ($e instanceof QueryException) {
            return $this->errorResponse('Database Error', 500);
        }

        $errors = config('app.debug') && ! app()->isProduction()
            ? [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]
            : null;

        return $this->errorResponse('Internal Server Error', 500, $errors);
    }
}
