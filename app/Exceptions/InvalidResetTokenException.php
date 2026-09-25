<?php

namespace App\Exceptions;

use Exception;

class InvalidResetTokenException extends Exception
{
    public function __construct(string $message = 'This password reset token is invalid or has expired.')
    {
        parent::__construct($message);
    }

    public function render(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'error' => 'Unprocessable Entity',
            'message' => $this->getMessage(),
        ], 422);
    }
}
