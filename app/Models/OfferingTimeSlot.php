<?php

declare(strict_types=1);

namespace App\Models;

class OfferingTimeSlot extends TenantModel
{
    protected $fillable = [
        'school_id',
        'course_offering_id',
        'time_slot_id',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function courseOffering()
    {
        return $this->belongsTo(CourseOffering::class);
    }

    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class);
    }
}
