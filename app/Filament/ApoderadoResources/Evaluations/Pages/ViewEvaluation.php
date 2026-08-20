<?php

declare(strict_types=1);

namespace App\Filament\ApoderadoResources\Evaluations\Pages;

use App\Filament\ApoderadoResources\Evaluations\EvaluationResource;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\Student;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewEvaluation extends ViewRecord
{
    protected static string $resource = EvaluationResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Evaluation Details'))
                    ->schema([
                        TextEntry::make('title')
                            ->label(__('Title')),
                        TextEntry::make('courseOffering.courseTemplate.name')
                            ->label(__('Course')),
                        TextEntry::make('courseOffering.section_name')
                            ->label(__('Section')),
                        TextEntry::make('courseOffering.academicPeriod.name')
                            ->label(__('Academic Period')),
                        TextEntry::make('description')
                            ->label(__('Description'))
                            ->columnSpanFull(),
                        TextEntry::make('due_at')
                            ->label(__('Due At'))
                            ->dateTime(),
                        TextEntry::make('published_at')
                            ->label(__('Published At'))
                            ->dateTime(),
                        TextEntry::make('max_score')
                            ->label(__('Max Score'))
                            ->numeric(),
                        TextEntry::make('weight')
                            ->label(__('Weight'))
                            ->numeric(),
                    ])
                    ->columns(2),
                Section::make(__('Submission Requirements'))
                    ->schema([
                        TextEntry::make('file_requirements')
                            ->label(__('File Requirements'))
                            ->state(function (Evaluation $record): string {
                                $evaluationable = $record->evaluationable;
                                if ($evaluationable && isset($evaluationable->file_requirements)) {
                                    return (string) $evaluationable->file_requirements;
                                }

                                return __('No specific file requirements.');
                            })
                            ->columnSpanFull(),
                        TextEntry::make('allow_late_submission')
                            ->label(__('Allow Late Submission'))
                            ->badge()
                            ->state(function (Evaluation $record): string {
                                $evaluationable = $record->evaluationable;
                                if ($evaluationable && isset($evaluationable->allow_late_submission)) {
                                    return $evaluationable->allow_late_submission ? 'yes' : 'no';
                                }

                                return 'no';
                            })
                            ->color(fn (string $state): string => $state === 'yes' ? 'success' : 'danger')
                            ->formatStateUsing(fn (string $state): string => $state === 'yes' ? __('Yes') : __('No')),
                        TextEntry::make('late_penalty')
                            ->label(__('Late Penalty'))
                            ->state(function (Evaluation $record): string {
                                $evaluationable = $record->evaluationable;
                                if ($evaluationable && isset($evaluationable->late_penalty_percent)) {
                                    return "{$evaluationable->late_penalty_percent}%";
                                }

                                return __('N/A');
                            }),
                    ])
                    ->columns(2),
                Section::make(__('Students Submission & Grades'))
                    ->schema([
                        RepeatableEntry::make('studentStatuses')
                            ->label(__('Linked Students'))
                            ->state(function (Evaluation $record) {
                                $linkedIds = EvaluationResource::linkedStudentUserIds();
                                if (empty($linkedIds)) {
                                    return collect();
                                }

                                $enrolledStudentIds = Enrollment::query()
                                    ->whereIn('student_id', $linkedIds)
                                    ->where('course_offering_id', $record->course_offering_id)
                                    ->where('status', 'active')
                                    ->pluck('student_id')
                                    ->all();

                                return Student::query()
                                    ->with('user:id,name')
                                    ->whereIn('id', $enrolledStudentIds)
                                    ->get()
                                    ->map(function (Student $student) use ($record) {
                                        $studentProfileId = (int) $student->getKey();

                                        $submission = $record->submissions()
                                            ->where('student_id', $studentProfileId)
                                            ->first();

                                        $grade = $record->grades()
                                            ->where('student_id', $studentProfileId)
                                            ->with('grader:id,name')
                                            ->first();

                                        $status = 'not_yet_submitted';
                                        if ($grade !== null) {
                                            $status = 'graded';
                                        } elseif ($submission !== null) {
                                            $status = 'submitted';
                                        }

                                        return [
                                            'student_name' => (string) ($student->user->name ?? __('Unknown')),
                                            'student_id' => $student->student_id,
                                            'status' => $status,
                                            'submitted_at' => $submission?->submitted_at,
                                            'attempt' => $submission?->attempt,
                                            'late_flag' => $submission?->late_flag,
                                            'score' => $grade?->score,
                                            'max_score' => $record->max_score,
                                            'feedback' => $grade?->feedback,
                                            'grader_name' => $grade?->grader?->name,
                                            'graded_at' => $grade?->created_at,
                                        ];
                                    });
                            })
                            ->schema([
                                TextEntry::make('student_name')
                                    ->label(__('Student')),
                                TextEntry::make('student_id')
                                    ->label(__('Matrícula')),
                                TextEntry::make('status')
                                    ->label(__('Status'))
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'not_yet_submitted' => 'warning',
                                        'submitted' => 'info',
                                        'graded' => 'success',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'not_yet_submitted' => __('Not Yet Submitted'),
                                        'submitted' => __('Submitted'),
                                        'graded' => __('Graded'),
                                        default => $state,
                                    }),
                                TextEntry::make('submitted_at')
                                    ->label(__('Submitted At'))
                                    ->dateTime(),
                                TextEntry::make('attempt')
                                    ->label(__('Attempt'))
                                    ->numeric(),
                                TextEntry::make('late_flag')
                                    ->label(__('Late'))
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'danger' : 'success')
                                    ->formatStateUsing(fn (bool $state): string => $state ? __('Yes') : __('No')),
                                TextEntry::make('score')
                                    ->label(__('Score'))
                                    ->numeric()
                                    ->badge()
                                    ->color(function ($state): string {
                                        if ($state === null) {
                                            return 'gray';
                                        }
                                        $num = (float) $state;
                                        if ($num >= 70) {
                                            return 'success';
                                        }
                                        if ($num >= 50) {
                                            return 'warning';
                                        }

                                        return 'danger';
                                    }),
                                TextEntry::make('max_score')
                                    ->label(__('Max Score'))
                                    ->numeric(),
                                TextEntry::make('feedback')
                                    ->label(__('Feedback'))
                                    ->columnSpanFull(),
                                TextEntry::make('grader_name')
                                    ->label(__('Teacher')),
                                TextEntry::make('graded_at')
                                    ->label(__('Graded At'))
                                    ->dateTime(),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
