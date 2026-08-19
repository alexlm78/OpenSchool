<?php

declare(strict_types=1);

namespace App\Models;

class AcademicPeriod extends TenantModel
{
    protected $fillable = [
        'school_id',
        'name',
        'type',
        'starts_at',
        'ends_at',
    ];

    protected $dates = [
        'starts_at',
        'ends_at',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function courseOfferings()
    {
        return $this->hasMany(CourseOffering::class);
    }
}
