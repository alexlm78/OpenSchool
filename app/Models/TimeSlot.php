<?php

namespace App\Models;

class TimeSlot extends TenantModel
{
    protected $fillable = [
        'school_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function offeringTimeSlots()
    {
        return $this->hasMany(OfferingTimeSlot::class);
    }
}
