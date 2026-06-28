<?php

namespace App\Models;

class Student extends TenantModel
{
    protected $fillable = [
        'school_id',
        'user_id',
        'student_id',
        'date_of_birth',
        'gender',
        'address',
        'phone',
    ];

    protected $dates = [
        'date_of_birth',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function guardians()
    {
        return $this->belongsToMany(Guardian::class, 'guardian_student')
                    ->withPivot('school_id')
                    ->withTimestamps();
    }
}
