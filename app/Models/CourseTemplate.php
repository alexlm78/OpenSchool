<?php

namespace App\Models;

class CourseTemplate extends TenantModel
{
    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
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
