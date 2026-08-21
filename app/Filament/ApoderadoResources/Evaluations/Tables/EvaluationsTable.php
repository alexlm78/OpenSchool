<?php

declare(strict_types=1);

namespace App\Filament\ApoderadoResources\Evaluations\Tables;

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
     * @param  array<int, int>  $studentUserIds
     * @param  array<int, int>  $studentProfileIds
     */
    public static function configure(Table $table, array $studentFilterOptions = [], array $courseOfferingOptions = [], array $studentUserIds = [], array $studentProfileIds = []): Table
    {
        return $table
            ->columns([
                TextColumn::make('derivedStudent')
                    ->label(__('Student'))
                    ->state(function (Evaluation $record, Table $livewireTable) use ($studentUserIds, $studentProfileIds): string {
                        $tableFilters = method_exists($livewireTable, 'getTableFilters') ? $livewireTable->getTableFilters() : [];
                        $filters = $tableFilters['data'] ?? [];
                        $selectedStudentProfileId = $filters['student']['value'] ?? null;

                        if (! empty($selectedStudentProfileId)) {
                            $student = Student::query()
                                ->with('user:id,name')
                                ->where('id', (int) $selectedStudentProfileId)
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
                            ->whereIn('id', $studentProfileIds !== [] ? $studentProfileIds : [-1])
                            ->whereExists(function (Builder $q) use ($record, $studentUserIds) {
                                $q->from('enrollments')
                                    ->whereColumn('enrollments.student_id', 'students.user_id')
                                    ->whereIn('enrollments.student_id', $studentUserIds !== [] ? $studentUserIds : [-1])
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
                    ->state(function (Evaluation $record, Table $livewireTable) use ($studentUserIds, $studentProfileIds): string {
                        $tableFilters = method_exists($livewireTable, 'getTableFilters') ? $livewireTable->getTableFilters() : [];
                        $filters = $tableFilters['data'] ?? [];
                        $selectedStudentProfileId = $filters['student']['value'] ?? null;

                        $userIdFromProfile = static fn (int $profileId): ?int => $studentProfileIds !== []
                            ? (collect($studentProfileIds)->search($profileId, true) !== false
                                ? ($studentUserIds[array_search($profileId, $studentProfileIds, true)] ?? null)
                                : null)
                            : null;

                        $targetUserIds = $studentUserIds;
                        if (! empty($selectedStudentProfileId)) {
                            $profileId = (int) $selectedStudentProfileId;
                            $fromIndex = $userIdFromProfile($profileId);
                            if ($fromIndex !== null) {
                                $targetUserIds = [$fromIndex];
                            } else {
                                $lookup = Student::query()->where('id', $profileId)->value('user_id');
                                $targetUserIds = $lookup !== null ? [(int) $lookup] : [];
                            }
                        }

                        if ($targetUserIds === []) {
                            return 'no_students';
                        }

                        $anySubmitted = false;
                        $anyGraded = false;
                        $allGraded = true;
                        $allSubmitted = true;

                        foreach ($targetUserIds as $studentId) {
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

                        if (\count($targetUserIds) === 1) {
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
                    ->modifyQueryUsing(function (Builder $query, $state) use ($studentUserIds, $studentProfileIds): Builder {
                        $value = $state['value'] ?? null;
                        if (empty($value)) {
                            return $query;
                        }

                        $profileId = (int) $value;
                        $pos = array_search($profileId, $studentProfileIds, true);
                        $targetUserId = $pos !== false ? ($studentUserIds[$pos] ?? null) : null;
                        if ($targetUserId === null && $profileId > 0) {
                            $found = Student::query()
                                ->where('id', $profileId)
                                ->value('user_id');
                            $targetUserId = $found !== null ? (int) $found : null;
                        }
                        if ($targetUserId === null) {
                            return $query;
                        }

                        return $query->whereExists(function (Builder $q) use ($targetUserId) {
                            $q->from('enrollments')
                                ->whereColumn('enrollments.course_offering_id', 'evaluations.course_offering_id')
                                ->where('enrollments.student_id', $targetUserId)
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
