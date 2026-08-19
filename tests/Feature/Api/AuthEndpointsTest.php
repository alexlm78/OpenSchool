<?php

declare(strict_types=1);

use App\Models\School;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(
    RefreshDatabase::class,
);

beforeEach(function (): void {
    $this->school = School::query()->create([
        'name' => 'Escuela API',
        'email' => 'api@example.com',
    ]);
    $this->app->make(TenantContext::class)->setSchoolId($this->school->id);
    $this->app->make(PermissionRegistrar::class)->setPermissionsTeamId($this->school->id);
    $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->studentRole = Role::firstOrCreate([
        'name' => 'student',
        'school_id' => $this->school->id,
        'guard_name' => 'web',
    ]);

    $this->studentPassword = 'StrongPass1!';
    $this->studentUser = User::factory()->createOne([
        'school_id' => $this->school->id,
        'password' => Hash::make($this->studentPassword),
    ]);
    $this->studentUser->assignRole($this->studentRole);
});

it('login endpoint returns bearer token and user payload with roles scoped', function (): void {
    $response = $this->postJson(route('api.auth.login'), [
        'email' => $this->studentUser->email,
        'password' => $this->studentPassword,
        'device_name' => 'PHPUnit Test Device',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'access_token',
                'token_type',
                'user' => ['id', 'school_id', 'name', 'email', 'roles', 'permissions'],
            ],
        ])
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.email', $this->studentUser->email)
        ->assertJsonPath('data.user.roles.0', 'student');
});

it('login endpoint returns 422 with wrong password', function (): void {
    $response = $this->postJson(route('api.auth.login'), [
        'email' => $this->studentUser->email,
        'password' => 'WrongPass_!',
        'device_name' => 'Test',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('me endpoint returns 401 without bearer token', function (): void {
    $response = $this->getJson(route('api.auth.me'));
    $response->assertUnauthorized();
});

it('me endpoint via sanctum actingAs returns current user', function (): void {
    Sanctum::actingAs($this->studentUser);

    $response = $this->getJson(route('api.auth.me'));

    $response->assertOk()
        ->assertJsonPath('data.id', $this->studentUser->id)
        ->assertJsonPath('data.school_id', $this->school->id)
        ->assertJsonPath('data.roles.0', 'student');
});

it('logout endpoint revokes the current token', function (): void {
    $token = $this->studentUser->createToken('TestLogout')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.auth.logout'))
        ->assertOk();

    $this->assertCount(0, $this->studentUser->fresh()->tokens()->get());
});

it('login endpoint is rate limited at 10 req/min per email+ip', function (): void {
    for ($i = 1; $i <= 11; $i++) {
        $response = $this->postJson(route('api.auth.login'), [
            'email' => $this->studentUser->email,
            'password' => 'NotARealPassword',
        ]);
    }

    $response->assertStatus(429);
});
