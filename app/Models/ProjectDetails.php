<?php

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

    protected function casts(): array
    {
        return [
            'evaluationable_id' => 'integer',
            'group_project' => 'boolean',
            'max_group_size' => 'integer',
        ];
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
