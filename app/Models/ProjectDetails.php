<?php

declare(strict_types=1);

namespace App\Models;

class ProjectDetails extends TenantModel
{
    protected $fillable = [
        'school_id',
        'evaluationable_type',
        'evaluationable_id',
        'group_project',
        'max_group_size',
        'rubric',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    protected function casts(): array
    {
        return [
            'evaluationable_id' => 'integer',
            'group_project' => 'boolean',
            'max_group_size' => 'integer',
        ];
    }
}
