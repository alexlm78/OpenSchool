<?php

namespace App\Models;

class SubmissionFile extends TenantModel
{
    protected $fillable = [
        'school_id',
        'submission_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
    ];

    protected function casts(): array
    {
        return [
            'submission_id' => 'integer',
            'file_size' => 'integer',
        ];
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }
}
