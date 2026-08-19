<?php

declare(strict_types=1);

namespace App\Models;

class Enrollment extends TenantModel
{
    protected $fillable = [
        'school_id',
        'student_id',
        'course_offering_id',
        'status',
        'enrolled_at',
        'completed_at',
    ];

    protected $dates = [
        'enrolled_at',
        'completed_at',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function courseOffering()
    {
        return $this->belongsTo(CourseOffering::class);
    }
}
