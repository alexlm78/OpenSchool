<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseOffering extends TenantModel
{
    protected $fillable = [
        'school_id',
        'academic_period_id',
        'course_template_id',
        'capacity',
        'section_name',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function courseTemplate(): BelongsTo
    {
        return $this->belongsTo(CourseTemplate::class);
    }

    public function offeringTimeSlots(): HasMany
    {
        return $this->hasMany(OfferingTimeSlot::class);
    }

    public function timeSlots(): BelongsToMany
    {
        return $this->belongsToMany(TimeSlot::class, 'offering_time_slots');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }
}
