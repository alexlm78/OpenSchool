<?php

declare(strict_types=1);

namespace App\Models;

class Grade extends TenantModel
{
    protected $fillable = [
        'school_id',
        'evaluation_id',
        'student_id',
        'score',
        'feedback',
        'graded_by',
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

    public function grader()
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    protected function casts(): array
    {
        return [
            'evaluation_id' => 'integer',
            'student_id' => 'integer',
            'score' => 'decimal:2',
            'graded_by' => 'integer',
        ];
    }
}
