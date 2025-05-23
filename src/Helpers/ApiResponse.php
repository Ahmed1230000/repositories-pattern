<?php

namespace Packages\AhmedMahmoud\RepositoryPattern\Src\Helpers;

/**
 * Trait ApiResponse
 *
 * Provides standardized JSON response methods for API success and error handling.
 */
trait ApiResponse
{
    /**
     * Return a successful JSON response.
     *
     * @param mixed|null $data
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function success($data = null, string $message = 'Success', int $code = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Return an error JSON response.
     *
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function error(string $message = 'Something went wrong', int $code = 400)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'data' => null,
        ], $code);
    }
}
