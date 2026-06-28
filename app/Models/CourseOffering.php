<?php

namespace App\Models;

class CourseOffering extends TenantModel
{
    protected $fillable = [
        'school_id',
        'academic_period_id',
        'course_template_id',
        'capacity',
        'section_name',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function courseTemplate()
    {
        return $this->belongsTo(CourseTemplate::class);
    }

    public function offeringTimeSlots()
    {
        return $this->hasMany(OfferingTimeSlot::class);
    }

    public function timeSlots()
    {
        return $this->belongsToMany(TimeSlot::class, 'offering_time_slots');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function teachingAssignments()
    {
        return $this->hasMany(TeachingAssignment::class);
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }
}
