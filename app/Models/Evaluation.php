<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Evaluation extends TenantModel
{
    protected $fillable = [
        'school_id',
        'course_offering_id',
        'title',
        'description',
        'max_score',
        'weight',
        'due_at',
        'published_at',
        'evaluationable_type',
        'evaluationable_id',
    ];

    protected $dates = [
        'due_at',
        'published_at',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function courseOffering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class);
    }

    public function evaluationable(): MorphTo
    {
        return $this->morphTo();
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }
}
