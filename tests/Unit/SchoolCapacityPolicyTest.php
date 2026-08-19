<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolCapacityPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_values_allow_unlimited(): void
    {
        $school = School::create([
            'name' => 'Escuela Default',
            'email' => 'default@example.com',
        ]);
        $school->refresh();

        $this->assertTrue($school->isUnlimitedCapacityAllowed());
        $this->assertSame(1, $school->getMinStudentsPerCourse());
        $this->assertSame(0, $school->getMaxStudentsPerCourse());
        $this->assertTrue($school->isCourseOfferingCapacityValid(0));
        $this->assertTrue($school->isCourseOfferingCapacityValid(30));
        $this->assertTrue($school->isCourseOfferingCapacityValid(100));
    }

    public function test_capacity_zero_rejected_when_unlimited_not_allowed(): void
    {
        $school = School::create([
            'name' => 'Escuela Sin Ilimitado',
            'email' => 'nounltd@example.com',
            'allow_unlimited_capacity' => false,
            'max_students_per_course' => 40,
        ]);

        $this->assertFalse($school->isUnlimitedCapacityAllowed());
        $this->assertFalse($school->isCourseOfferingCapacityValid(0));
        $this->assertTrue($school->isCourseOfferingCapacityValid(1));
        $this->assertTrue($school->isCourseOfferingCapacityValid(40));
        $this->assertFalse($school->isCourseOfferingCapacityValid(41));
    }

    public function test_min_capacity_enforced(): void
    {
        $school = School::create([
            'name' => 'Escuela Min 10',
            'email' => 'min10@example.com',
            'min_students_per_course' => 10,
            'max_students_per_course' => 0,
            'allow_unlimited_capacity' => true,
        ]);

        $this->assertFalse($school->isCourseOfferingCapacityValid(1));
        $this->assertFalse($school->isCourseOfferingCapacityValid(9));
        $this->assertTrue($school->isCourseOfferingCapacityValid(10));
        $this->assertTrue($school->isCourseOfferingCapacityValid(15));
    }

    public function test_max_capacity_enforced(): void
    {
        $school = School::create([
            'name' => 'Escuela Max 25',
            'email' => 'max25@example.com',
            'min_students_per_course' => 0,
            'max_students_per_course' => 25,
            'allow_unlimited_capacity' => false,
        ]);

        $this->assertTrue($school->isCourseOfferingCapacityValid(1));
        $this->assertTrue($school->isCourseOfferingCapacityValid(25));
        $this->assertFalse($school->isCourseOfferingCapacityValid(26));
    }
}
