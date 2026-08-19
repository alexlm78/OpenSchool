<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'logo',
        'min_students_per_course',
        'max_students_per_course',
        'allow_unlimited_capacity',
    ];

    protected $dates = [
        'email_verified_at',
    ];

    public function isUnlimitedCapacityAllowed(): bool
    {
        return (bool) $this->allow_unlimited_capacity;
    }

    public function getMinStudentsPerCourse(): int
    {
        return max(0, (int) ($this->min_students_per_course ?? 0));
    }

    public function getMaxStudentsPerCourse(): int
    {
        return max(0, (int) ($this->max_students_per_course ?? 0));
    }

    public function isCourseOfferingCapacityValid(int $capacity): bool
    {
        if ($capacity === 0) {
            return $this->isUnlimitedCapacityAllowed();
        }

        $min = $this->getMinStudentsPerCourse();
        $max = $this->getMaxStudentsPerCourse();

        if ($min > 0 && $capacity < $min) {
            return false;
        }

        if ($max > 0 && $capacity > $max) {
            return false;
        }

        return true;
    }

    public function capacityValidationMessage(): string
    {
        $min = $this->getMinStudentsPerCourse();
        $max = $this->getMaxStudentsPerCourse();
        $unlimited = $this->isUnlimitedCapacityAllowed();

        $parts = [];
        if ($min > 0) {
            $parts[] = "mínimo $min";
        }
        if ($max > 0) {
            $parts[] = "máximo $max";
        } else {
            $parts[] = $unlimited ? 'máximo ilimitado (0)' : 'máximo no ilimitado (0 no permitido)';
        }

        return implode(', ', $parts);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'min_students_per_course' => 'integer',
            'max_students_per_course' => 'integer',
            'allow_unlimited_capacity' => 'boolean',
        ];
    }
}
