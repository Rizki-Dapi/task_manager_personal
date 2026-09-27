<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidCredentialsException extends Exception
{
    public function __construct(string $message = 'Invalid email or password.')
    {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'Unauthorized',
            'message' => $this->getMessage(),
        ], 401);
    }
}
