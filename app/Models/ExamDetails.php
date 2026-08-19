<?php

declare(strict_types=1);

namespace App\Models;

class ExamDetails extends TenantModel
{
    protected $fillable = [
        'school_id',
        'evaluationable_type',
        'evaluationable_id',
        'exam_date',
        'duration_minutes',
        'location',
        'modality',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    protected function casts(): array
    {
        return [
            'evaluationable_id' => 'integer',
            'exam_date' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }
}
