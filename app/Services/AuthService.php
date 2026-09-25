<?php

namespace App\Services;

use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\InvalidResetTokenException;
use App\Models\User;
use App\Repositories\Interfaces\LogRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly LogRepositoryInterface $logRepository,
    ) {}

    public function register(array $data): array
    {
        $user = $this->userRepository->create($data);
        $user->sendEmailVerificationNotification();

        $token = JWTAuth::fromUser($user);

        $this->logRepository->record([
            'user_id' => $user->id,
            'action' => 'user.registered',
        ]);

        return ['user' => $user, 'token' => $token];
    }

    public function login(array $credentials)
    {
        if (! $token = JWTAuth::attempt($credentials)) {
            throw new InvalidCredentialsException();
        }

        $user = JWTAuth::user();

        if (Hash::needsRehash($user->password)) {
            $this->userRepository->update($user, ['password' => $credentials['password']]);
        }

        $this->logRepository->record([
            'user_id' => $user->id,
            'action' => 'user.login'
        ]);

        return ['user' => $user, 'token' => $token];
    }

    public function logout(): void
    {
        $userId = auth('api')->id();

        JWTAuth::invalidate(JWTAuth::getToken());

        $this->logRepository->record([
            'user_id' => $userId,
            'action' => 'user.logout'
        ]);
    }

    public function me(): ?User
    {
        return auth('api')->user();
    }

    public function refresh()
    {
        try {
            $newToken = JWTAuth::parseToken()->refresh();
        } catch (JWTException $e) {
            throw new InvalidCredentialsException('Your session has expired. Please log in again.');
        }

        $user = JWTAuth::setToken($newToken)->authenticate();

        $this->logRepository->record([
            'user_id' => $user->id,
            'action' => 'user.token_refreshed'
        ]);

        return ['user' => $user, 'token' => $newToken];
    }

    public function verifyEmail(int $userId, string $hash)
    {
        $user = $this->userRepository->findById($userId);

        if (! $user || ! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return ['user' => $user, 'already_verified' => false];
        }

        if ($user->hasVerifiedEmail()) {
            return ['user' => $user, 'already_verified' => true];
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        $this->logRepository->record([
            'user_id' => $user->id,
            'action' => 'user.email_verified',
        ]);

        return ['user' => $user, 'already_verified' => false];
    }

    public function resendVerication(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return false;
        }

        $user->sendEmailVerificationNotification();

        return true;
    }

    public function sendPasswordResetLink(string $email): void
    {
        Password::sendResetLink(['email' => $email]);
    }

    public function resetPassword(array $data)
    {
        $status = Password::reset(
            $data,
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)]);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new InvalidResetTokenException();
        }

        $this->logRepository->record([
            'action' => 'user.passsword_reset',
            'context' => ['email' => $data['email']]
        ]);
    }

    public function updateProfile(User $user, array $data): User
    {
        $updated = $this->userRepository->update($user, $data);

        $this->logRepository->record([
            'user_id' => $updated->id,
            'action' => 'user.update_data',
            'context' => ['field' => array_keys($data)]
        ]);

        return $updated;
    }

    public function deleteAccount(User $user): void
    {
        $userId = $user->id;

        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (\Throwable $e) {
        }

        $this->userRepository->delete($user);

        $this->logRepository->record([
            'user_id' => $userId,
            'action' => 'user.self_delete'
        ]);
    }
}
