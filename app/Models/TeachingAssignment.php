<?php

namespace App\Models;

class TeachingAssignment extends TenantModel
{
    protected $fillable = [
        'school_id',
        'course_offering_id',
        'teacher_id',
    ];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'course_offering_id' => 'integer',
            'teacher_id' => 'integer',
        ];
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function courseOffering()
    {
        return $this->belongsTo(CourseOffering::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
