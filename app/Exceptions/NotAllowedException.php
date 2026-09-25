<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class NotAllowedException extends Exception
{
    public function __construc(string $massage = 'You are not allowed to process this endpoint')
    {
        parent::__construct($massage);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'Unprocessable Entity',
            'message' => $this->getMessage()
        ], Response::HTTP_FORBIDDEN);
    }
}
