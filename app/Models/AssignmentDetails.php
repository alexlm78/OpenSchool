<?php

declare(strict_types=1);

namespace App\Models;

class AssignmentDetails extends TenantModel
{
    protected $fillable = [
        'school_id',
        'evaluationable_type',
        'evaluationable_id',
        'description',
        'file_requirements',
        'allow_late_submission',
        'late_penalty_percent',
        'late_until',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    protected function casts(): array
    {
        return [
            'evaluationable_id' => 'integer',
            'allow_late_submission' => 'boolean',
            'late_penalty_percent' => 'integer',
            'late_until' => 'datetime',
        ];
    }
}
