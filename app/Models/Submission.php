<?php

declare(strict_types=1);

namespace App\Models;

class Submission extends TenantModel
{
    protected $fillable = [
        'school_id',
        'evaluation_id',
        'student_id',
        'submitted_at',
        'status',
        'attempt',
        'late_flag',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function submissionFiles()
    {
        return $this->hasMany(SubmissionFile::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'attempt' => 'integer',
            'late_flag' => 'boolean',
        ];
    }
}
