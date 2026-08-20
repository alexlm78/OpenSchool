<?php

declare(strict_types=1);

namespace App\Filament\ApoderadoResources\Evaluations\Tables;

use App\Filament\ApoderadoResources\Evaluations\EvaluationResource;
use App\Models\Evaluation;
use App\Models\Student;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EvaluationsTable
{
    /**
     * @param  array<string, string>  $studentFilterOptions
     * @param  array<string, string>  $courseOfferingOptions
     */
    public static function configure(Table $table, array $studentFilterOptions = [], array $courseOfferingOptions = []): Table
    {
        return $table
            ->columns([
                TextColumn::make('derivedStudent')
                    ->label(__('Student'))
                    ->state(function (Evaluation $record, Table $livewireTable): string {
                        $tableFilters = method_exists($livewireTable, 'getTableFilters') ? $livewireTable->getTableFilters() : [];
                        $filters = $tableFilters['data'] ?? [];
                        $selectedStudentId = $filters['student']['value'] ?? null;

                        $linkedIds = EvaluationResource::linkedStudentUserIds();

                        if (! empty($selectedStudentId)) {
                            $student = Student::query()
                                ->with('user:id,name')
                                ->where('id', (int) $selectedStudentId)
                                ->first();
                            if ($student instanceof Student && $student->user) {
                                $name = (string) $student->user->name;
                                if (! empty($student->student_id)) {
                                    $name .= " ({$student->student_id})";
                                }

                                return $name;
                            }
                        }

                        $students = Student::query()
                            ->with('user:id,name')
                            ->whereIn('id', $linkedIds)
                            ->whereExists(function (Builder $q) use ($record) {
                                $q->from('enrollments')
                                    ->whereColumn('enrollments.student_id', 'students.id')
                                    ->where('enrollments.course_offering_id', $record->course_offering_id)
                                    ->where('enrollments.status', 'active');
                            })
                            ->get();

                        if ($students->isEmpty()) {
                            return __('N/A');
                        }

                        if ($students->count() === 1) {
                            $s = $students->first();
                            $name = (string) ($s->user->name ?? '');
                            if (! empty($s->student_id)) {
                                $name .= " ({$s->student_id})";
                            }

                            return $name;
                        }

                        return $students->map(static function (Student $s): string {
                            $name = (string) ($s->user->name ?? __('Unknown'));
                            if (! empty($s->student_id)) {
                                $name .= " ({$s->student_id})";
                            }

                            return $name;
                        })->join(', ');
                    })
                    ->searchable()
                    ->sortable(query: function (Builder $query): Builder {
                        return $query;
                    }),
                TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('due_at')
                    ->label(__('Due Date'))
                    ->dateTime('M d, Y H:i')
                    ->badge()
                    ->color(static function (Evaluation $record): string {
                        $due = $record->getAttributeValue('due_at');
                        if ($due === null) {
                            return 'gray';
                        }
                        $hoursLeft = now()->diffInHours($due, false);
                        if ($hoursLeft < 0) {
                            return 'gray';
                        }

                        return match (true) {
                            $hoursLeft <= 24 => 'danger',
                            $hoursLeft <= 72 => 'warning',
                            default => 'info',
                        };
                    })
                    ->sortable(),
                TextColumn::make('max_score')
                    ->label(__('Max Score'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('computedStatus')
                    ->label(__('Status'))
                    ->badge()
                    ->state(function (Evaluation $record, Table $livewireTable): string {
                        $tableFilters = method_exists($livewireTable, 'getTableFilters') ? $livewireTable->getTableFilters() : [];
                        $filters = $tableFilters['data'] ?? [];
                        $selectedStudentId = $filters['student']['value'] ?? null;
                        $linkedIds = EvaluationResource::linkedStudentUserIds();

                        $studentIds = ! empty($selectedStudentId) && \in_array((int) $selectedStudentId, $linkedIds, true)
                            ? [(int) $selectedStudentId]
                            : $linkedIds;

                        if (empty($studentIds)) {
                            return 'no_students';
                        }

                        $anySubmitted = false;
                        $anyGraded = false;
                        $allGraded = true;
                        $allSubmitted = true;

                        foreach ($studentIds as $studentId) {
                            $hasSubmission = $record->submissions()
                                ->where('student_id', $studentId)
                                ->exists();

                            $hasGrade = $record->grades()
                                ->where('student_id', $studentId)
                                ->exists();

                            if ($hasSubmission) {
                                $anySubmitted = true;
                            } else {
                                $allSubmitted = false;
                            }

                            if ($hasGrade) {
                                $anyGraded = true;
                            } else {
                                $allGraded = false;
                            }
                        }

                        if (\count($studentIds) === 1) {
                            if ($allGraded) {
                                return 'graded';
                            }
                            if ($allSubmitted) {
                                return 'submitted';
                            }

                            return 'not_yet_submitted';
                        }

                        if ($allGraded) {
                            return 'graded';
                        }
                        if ($anyGraded) {
                            return 'partially_graded';
                        }
                        if ($allSubmitted) {
                            return 'submitted';
                        }
                        if ($anySubmitted) {
                            return 'partially_submitted';
                        }

                        return 'not_yet_submitted';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'not_yet_submitted' => 'warning',
                        'partially_submitted' => 'warning',
                        'submitted' => 'info',
                        'partially_graded' => 'info',
                        'graded' => 'success',
                        'no_students' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'not_yet_submitted' => __('Not Yet Submitted'),
                        'partially_submitted' => __('Partially Submitted'),
                        'submitted' => __('Submitted'),
                        'partially_graded' => __('Partially Graded'),
                        'graded' => __('Graded'),
                        'no_students' => __('N/A'),
                        default => $state,
                    }),
            ])
            ->filters([
                SelectFilter::make('student')
                    ->label(__('Estudiante'))
                    ->options($studentFilterOptions)
                    ->modifyQueryUsing(function (Builder $query, $state): Builder {
                        $value = $state['value'] ?? null;
                        if (empty($value)) {
                            return $query;
                        }

                        $studentId = (int) $value;

                        return $query->whereExists(function (Builder $q) use ($studentId) {
                            $q->from('enrollments')
                                ->whereColumn('enrollments.course_offering_id', 'evaluations.course_offering_id')
                                ->where('enrollments.student_id', $studentId)
                                ->where('enrollments.status', 'active');
                        });
                    }),
                SelectFilter::make('courseOffering')
                    ->label(__('Course Offering'))
                    ->options($courseOfferingOptions)
                    ->modifyQueryUsing(function (Builder $query, $state): Builder {
                        $value = $state['value'] ?? null;
                        if (empty($value)) {
                            return $query;
                        }

                        return $query->where('course_offering_id', (int) $value);
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                //
            ])
            ->defaultSort('due_at', 'asc')
            ->paginationPageOptions([10, 25, 50]);
    }
}
