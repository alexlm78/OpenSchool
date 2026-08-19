<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

final class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var User|null $user */
        $user = User::query()
            ->where('email', $credentials['email'])
            ->first();

        if (! $user instanceof User || ! Hash::check($credentials['password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $schoolId = $user->getAttributeValue('school_id');
        if ($schoolId !== null) {
            PermissionRegistrar::setPermissionsTeamId((int) $schoolId);
        }

        $deviceName = (string) ($credentials['device_name'] ?? $request->userAgent() ?? 'api-device');
        $token = $user->createToken($deviceName);

        return new JsonResponse([
            'data' => [
                'access_token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => null,
                'user' => [
                    'id' => $user->getKey(),
                    'school_id' => $schoolId,
                    'name' => $user->getAttributeValue('name'),
                    'email' => $user->getAttributeValue('email'),
                    'email_verified_at' => $user->getAttributeValue('email_verified_at'),
                    'locale' => $user->getAttributeValue('locale'),
                    'roles' => $user->getRoleNames()->values()->toArray(),
                    'permissions' => $user->getAllPermissions()->pluck('name')->values()->toArray(),
                ],
            ],
        ], JsonResponse::HTTP_OK);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $schoolId = $user->getAttributeValue('school_id');

        return new JsonResponse([
            'data' => [
                'id' => $user->getKey(),
                'school_id' => $schoolId,
                'name' => $user->getAttributeValue('name'),
                'email' => $user->getAttributeValue('email'),
                'email_verified_at' => $user->getAttributeValue('email_verified_at'),
                'locale' => $user->getAttributeValue('locale'),
                'timezone' => $user->getAttributeValue('timezone'),
                'phone' => $user->getAttributeValue('phone'),
                'created_at' => $user->getAttributeValue('created_at'),
                'updated_at' => $user->getAttributeValue('updated_at'),
                'roles' => $user->getRoleNames()->values()->toArray(),
                'permissions' => $user->getAllPermissions()->pluck('name')->values()->toArray(),
            ],
        ], JsonResponse::HTTP_OK);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $currentAccessToken = $user->currentAccessToken();
        if ($currentAccessToken !== null) {
            $currentAccessToken->delete();
        }

        return new JsonResponse([
            'data' => [
                'message' => __('auth.logged_out'),
                'revoked_token_id' => $currentAccessToken?->getKey(),
            ],
        ], JsonResponse::HTTP_OK);
    }
}
