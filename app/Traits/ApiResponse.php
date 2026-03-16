<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponse
{
    /**
     * Core response method.
     *
     * @param string $message
     * @param mixed $data
     * @param int $statusCode
     * @param bool $isSuccess
     * @return JsonResponse
     */
    public function coreResponse(string $message, mixed $data = null, int $statusCode, bool $isSuccess = true): JsonResponse
    {
        // Check the params
        if (!$message) return response()->json(['message' => 'Message is required'], 500);

        // Send the response
        if ($isSuccess) {
            $payload = [
                'message' => $message,
                'success' => true,
                'code' => $statusCode,
            ];
            if ($data) {
                $payload['data'] = $data;
            }
        } else {
            $payload = [
                'message' => $message,
                'success' => false,
                'code' => $statusCode,
            ];
            if ($data) {
                $payload['errors'] = $data;
            }
        }
        
        return response()->json($payload, $statusCode);
    }

    /**
     * Send any success response.
     *
     * @param string $message
     * @param mixed $data
     * @param int $statusCode
     * @return JsonResponse
     */
    public function success(string $message, mixed $data = null, int $statusCode = Response::HTTP_OK): JsonResponse
    {
        return $this->coreResponse($message, $data, $statusCode);
    }

    /**
     * Send any error response.
     *
     * @param string $message
     * @param int $statusCode
     * @param mixed $errors
     * @return JsonResponse
     */
    public function error(string $message, int $statusCode = Response::HTTP_BAD_REQUEST, mixed $errors = null): JsonResponse
    {
        return $this->coreResponse($message, $errors, $statusCode, false);
    }

    /**
     * Send a created response (201).
     *
     * @param string $message
     * @param mixed $data
     * @return JsonResponse
     */
    public function created(mixed $data, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->success($message, $data, Response::HTTP_CREATED);
    }

    /**
     * Send a no content response (204).
     *
     * @return JsonResponse
     */
    public function noContent(): JsonResponse
    {
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Send a resource response (200).
     *
     * @param JsonResource $resource
     * @param string $message
     * @return JsonResponse
     */
    public function resource(JsonResource $resource, string $message = 'Success'): JsonResponse
    {
        return $this->success($message, $resource);
    }

    /**
     * Send a collection response (200).
     *
     * @param ResourceCollection $collection
     * @param string $message
     * @return JsonResponse
     */
    public function collection(ResourceCollection $collection, string $message = 'Success'): JsonResponse
    {
        return $this->success($message, $collection);
    }

    /**
     * Send a paginated response (200).
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    public function paginated(mixed $data, string $message = 'Success'): JsonResponse
    {
        // Support both API Resources and plain paginator/collection instances
        if ($data instanceof JsonResource) {
            $response = $data->response()->getData(true);
        } elseif ($data instanceof ResourceCollection) {
            $response = $data->response()->getData(true);
        } elseif ($data instanceof LengthAwarePaginator) {
            $response = [
                'data' => $data->items(),
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'last_page' => $data->lastPage(),
            ];
        } elseif ($data instanceof Paginator) {
            $response = [
                'data' => $data->items(),
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
            ];
        } elseif ($data instanceof Collection) {
            $response = [
                'data' => $data,
            ];
        } else {
            $response = [
                'data' => $data,
            ];
        }

        $response['success'] = true;
        $response['message'] = $message;
        $response['code'] = Response::HTTP_OK;

        return response()->json($response, Response::HTTP_OK);
    }

    /**
     * Send a validation error response (422).
     *
     * @param mixed $errors
     * @param string $message
     * @return JsonResponse
     */
    public function validationError(mixed $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error($message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    /**
     * Send an unauthorized response (401).
     *
     * @param string $message
     * @return JsonResponse
     */
    public function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Send a forbidden response (403).
     *
     * @param string $message
     * @return JsonResponse
     */
    public function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Send a not found response (404).
     *
     * @param string $message
     * @return JsonResponse
     */
    public function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, Response::HTTP_NOT_FOUND);
    }
    
    /**
     * Send a server error response (500).
     *
     * @param string $message
     * @param mixed $exception
     * @return JsonResponse
     */
    public function serverError(string $message = 'Internal Server Error', mixed $exception = null): JsonResponse
    {
        $errors = config('app.debug') ? $exception : null;
        return $this->error($message, Response::HTTP_INTERNAL_SERVER_ERROR, $errors);
    }
}
