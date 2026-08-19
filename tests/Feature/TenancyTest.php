<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CourseTemplate;
use App\Models\School;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(TenantContext::class)->setSchoolId(null);

        Route::middleware('web')->get('/_test/tenant', function (TenantContext $tenantContext) {
            return response()->json([
                'school_id' => $tenantContext->getSchoolId(),
            ]);
        });
    }

    public function test_tenancy_scope_filters_models_by_tenant_context(): void
    {
        $schoolA = School::create(['name' => 'Escuela A', 'email' => 'escuela-a@example.com']);
        $schoolB = School::create(['name' => 'Escuela B', 'email' => 'escuela-b@example.com']);

        CourseTemplate::create([
            'school_id' => $schoolA->id,
            'name' => 'Matemáticas',
            'code' => 'MAT-101',
            'description' => 'Curso A',
        ]);

        CourseTemplate::create([
            'school_id' => $schoolB->id,
            'name' => 'Lenguaje',
            'code' => 'LEN-101',
            'description' => 'Curso B',
        ]);

        app(TenantContext::class)->setSchoolId($schoolA->id);
        $templatesForA = CourseTemplate::query()->pluck('school_id')->unique()->all();
        $this->assertSame([$schoolA->id], $templatesForA);

        app(TenantContext::class)->setSchoolId($schoolB->id);
        $templatesForB = CourseTemplate::query()->pluck('school_id')->unique()->all();
        $this->assertSame([$schoolB->id], $templatesForB);
    }

    public function test_middleware_sets_tenant_from_authenticated_user_and_ignores_request_input(): void
    {
        $schoolA = School::create(['name' => 'Escuela A', 'email' => 'escuela-a@example.com']);
        $schoolB = School::create(['name' => 'Escuela B', 'email' => 'escuela-b@example.com']);

        $userA = $this->createAuthenticatableUserForSchool($schoolA->id);

        $response = $this->actingAs($userA)->get('/_test/tenant?school_id='.$schoolB->id);

        $response->assertOk();
        $response->assertJson([
            'school_id' => $schoolA->id,
        ]);
    }

    private function createAuthenticatableUserForSchool(int $schoolId): Authenticatable
    {
        $user = User::factory()->createOne([
            'school_id' => $schoolId,
        ]);

        if (! $user instanceof Authenticatable) {
            throw new \RuntimeException('Expected a User that implements Authenticatable.');
        }

        return $user;
    }
}
