<?php

declare(strict_types=1);

namespace App\Models;

class Guardian extends TenantModel
{
    protected $fillable = [
        'school_id',
        'user_id',
        'relationship',
        'phone',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'guardian_student')
            ->withPivot('school_id')
            ->withTimestamps();
    }
}
