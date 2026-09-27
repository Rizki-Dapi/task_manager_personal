<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\ForgetPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\UpdatedUserRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(RegisterRequest $req): JsonResponse
    {
        $result = $this->authService->register($req->validated());

        return response()->json(ApiFormatter::createJson('Registration Successful', [
            'user' => new UserResource($result['user']),
            'access_token' => $result['token'],
            'token_type' => 'bearer',
        ]), Response::HTTP_CREATED);
    }

    public function login(LoginRequest $req)
    {
        $result = $this->authService->login($req->validated());

        return response()->json(ApiFormatter::createJson('Login Successful', [
            'user' => new UserResource($result['user']),
            'access_token' => $result['token'],
            'token_type' => 'bearer',
        ]), Response::HTTP_OK);
    }

    public function logout()
    {
        $this->authService->logout();

        return response()->json(ApiFormatter::createJson('Logout successful'));
    }

    public function me()
    {
        $user = $this->authService->me();

        return response()->json(ApiFormatter::createJson('User retrieved successfully', [
            'user' => new UserResource($user),
        ]), Response::HTTP_OK);
    }

    public function refreshToken()
    {
        $result = $this->authService->refresh();

        return response()->json(ApiFormatter::createJson('Token refreshed successfully', [
            'user' => new UserResource($result['user']),
            'access_token' => $result['token'],
            'token_type' => 'bearer',
        ]), Response::HTTP_OK);
    }

    public function updateProfile(UpdatedUserRequest $req)
    {
        $user = $this->authService->updateProfile($req->user(), $req->validated());

        return response()->json(ApiFormatter::createJson('Profile updated successfully.', [
            'user' => new UserResource($user),
        ]), Response::HTTP_OK);
    }

    public function deleteAccount(Request $req)
    {
        $this->authService->deleteAccount($req->validated());

        return response()->json(ApiFormatter::createJson('Account deleted successfully.'));
    }

    public function verifyEmail(int $id, string $hash)
    {
        $result = $this->authService->verifyEmail($id, $hash);

        if (! $result['user']) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Invalid verification link',
            ], Response::HTTP_FORBIDDEN);
        }

        $message = $result['already_verified']
            ? 'Email already verified.'
            : 'Email verified successfully.';

        return response()->json(ApiFormatter::createJson($message), Response::HTTP_OK);
    }

    public function resendVerification(Request $req)
    {
        $sent = $this->authService->resendVerication($req->user());

        $message = $sent
            ? 'Verification link sent.'
            : 'Email already verified.';

        return response()->json(ApiFormatter::createJson($message), Response::HTTP_OK);
    }

    public function forgetPassword(ForgetPasswordRequest $req)
    {
        $this->authService->sendPasswordResetLink($req->validated('email'));

        return response()->json(ApiFormatter::createJson('Password reset successfully.'), Response::HTTP_OK);
    }

    public function resetPassword(ResetPasswordRequest $req)
    {
        $this->authService->resetPassword($req->validated());

        return response()->json(ApiFormatter::createJson('Password reset succesfully.'), Response::HTTP_OK);
    }
}
