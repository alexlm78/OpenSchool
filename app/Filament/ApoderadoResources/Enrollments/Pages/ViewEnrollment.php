<?php

declare(strict_types=1);

namespace App\Filament\ApoderadoResources\Enrollments\Pages;

use App\Filament\ApoderadoResources\Enrollments\EnrollmentResource;
use App\Models\Enrollment;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewEnrollment extends ViewRecord
{
    protected static string $resource = EnrollmentResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Enrollment Details'))
                    ->schema([
                        TextEntry::make('student.name')
                            ->label(__('Student')),
                        TextEntry::make('student.email')
                            ->label(__('Student Email')),
                        TextEntry::make('courseOffering.courseTemplate.name')
                            ->label(__('Course Name')),
                        TextEntry::make('courseOffering.courseTemplate.code')
                            ->label(__('Course Code')),
                        TextEntry::make('courseOffering.section_name')
                            ->label(__('Section')),
                        TextEntry::make('courseOffering.academicPeriod.name')
                            ->label(__('Academic Period')),
                        TextEntry::make('status')
                            ->label(__('Status'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'active' => 'success',
                                'completed' => 'info',
                                'dropped' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('enrolled_at')
                            ->label(__('Enrolled At'))
                            ->dateTime(),
                        TextEntry::make('completed_at')
                            ->label(__('Completed At'))
                            ->dateTime(),
                    ])
                    ->columns(2),
                Section::make(__('Evaluation Summary'))
                    ->schema([
                        TextEntry::make('evaluationSummary')
                            ->label(__('Grades Overview'))
                            ->state(function (Enrollment $record): string {
                                $enrollment = $record;
                                $studentId = $enrollment->student_id;

                                $evaluations = $enrollment->courseOffering->evaluations()
                                    ->with(['grades' => fn ($q) => $q->where('student_id', $studentId)])
                                    ->get();

                                if ($evaluations->isEmpty()) {
                                    return __('No evaluations available yet.');
                                }

                                $lines = [];
                                $totalWeight = 0;
                                $weightedScore = 0;

                                foreach ($evaluations as $evaluation) {
                                    $grade = $evaluation->grades->first();
                                    $score = $grade?->score;
                                    $weight = (float) $evaluation->weight;
                                    $maxScore = (float) $evaluation->max_score;

                                    $line = "{$evaluation->title}: ";
                                    if ($score !== null) {
                                        $line .= "{$score}/{$maxScore} (weight: {$weight})";
                                        if ($maxScore > 0) {
                                            $totalWeight += $weight;
                                            $weightedScore += ($score / $maxScore) * $weight;
                                        }
                                    } else {
                                        $line .= __('Not graded yet')." (weight: {$weight})";
                                    }
                                    $lines[] = $line;
                                }

                                $summary = implode("\n", $lines);
                                if ($totalWeight > 0) {
                                    $final = round(($weightedScore / $totalWeight) * 100, 2);
                                    $summary .= "\n\n".__('Weighted Progress').": {$final}% ({$totalWeight} total weight)";
                                }

                                return $summary;
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
