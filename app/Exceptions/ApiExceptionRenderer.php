<?php

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

final class ApiExceptionRenderer
{
    public function render(Throwable $exception): JsonResponse
    {
        if ($exception instanceof ValidationException) {
            return ApiResponse::error(
                'validation_error',
                'The given data was invalid.',
                422,
                $exception->errors(),
            );
        }

        if ($exception instanceof AuthenticationException) {
            return ApiResponse::error('unauthenticated', 'Unauthenticated.', 401);
        }

        if ($exception instanceof AuthorizationException || $exception instanceof AccessDeniedHttpException) {
            return ApiResponse::error('forbidden', 'Forbidden.', 403);
        }

        if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
            return ApiResponse::error('not_found', 'Not found.', 404);
        }

        if ($exception instanceof TooManyRequestsHttpException) {
            return ApiResponse::error('too_many_requests', 'Too many requests.', 429)
                ->withHeaders($exception->getHeaders());
        }

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();

            return match (true) {
                $status === 401 => ApiResponse::error('unauthenticated', 'Unauthenticated.', 401),
                $status === 403 => ApiResponse::error('forbidden', 'Forbidden.', 403),
                $status === 404 => ApiResponse::error('not_found', 'Not found.', 404),
                $status === 429 => ApiResponse::error('too_many_requests', 'Too many requests.', 429),
                $status >= 400 && $status < 500 => ApiResponse::error('request_error', 'Request could not be processed.', $status),
                default => ApiResponse::error('server_error', 'Server error.', 500),
            };
        }

        return ApiResponse::error('server_error', 'Server error.', 500);
    }
}
