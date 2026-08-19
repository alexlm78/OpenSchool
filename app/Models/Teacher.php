<?php

declare(strict_types=1);

namespace App\Models;

class Teacher extends TenantModel
{
    protected $fillable = [
        'school_id',
        'user_id',
        'employee_id',
        'department',
        'specialization',
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
}
