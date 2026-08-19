<?php

declare(strict_types=1);

namespace App\Filament\AlumnoResources\Evaluations\Tables;

use App\Filament\AlumnoResources\Submissions\SubmissionResource;
use App\Models\Evaluation;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class EvaluationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('courseOffering.courseTemplate.name')
                    ->label(__('Course'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('due_at')
                    ->label(__('Due Date'))
                    ->dateTime()
                    ->sortable()
                    ->badge()
                    ->color(static function (mixed $state): string {
                        if ($state === null) {
                            return 'gray';
                        }
                        $hoursLeft = now()->diffInHours($state, false);

                        return match (true) {
                            $hoursLeft <= 24 => 'danger',
                            $hoursLeft <= 72 => 'warning',
                            default => 'info',
                        };
                    }),
                TextColumn::make('published_at')
                    ->label(__('Published At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('max_score')
                    ->label(__('Max Score'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('weight')
                    ->label(__('Weight'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('submission_status')
                    ->label(__('Status'))
                    ->badge()
                    ->state(function (Evaluation $record): string {
                        $studentId = Auth::id();
                        if (! $studentId) {
                            return 'not_yet_submitted';
                        }

                        $hasSubmission = $record->submissions()
                            ->where('student_id', $studentId)
                            ->exists();

                        if (! $hasSubmission) {
                            return 'not_yet_submitted';
                        }

                        $hasGrade = $record->grades()
                            ->where('student_id', $studentId)
                            ->exists();

                        if ($hasGrade) {
                            return 'graded';
                        }

                        return 'submitted';
                    })
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
            ])
            ->filters([
                TernaryFilter::make('submitted')
                    ->label(__('Has Submission'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('Submitted'))
                    ->falseLabel(__('Not Submitted Yet'))
                    ->queries(
                        true: static function ($query): void {
                            $studentId = Auth::id();
                            if (! $studentId) {
                                $query->whereRaw('1 = 0');

                                return;
                            }
                            $query->whereExists(function ($q) use ($studentId): void {
                                $q->from('submissions')
                                    ->whereColumn('submissions.evaluation_id', 'evaluations.id')
                                    ->where('submissions.student_id', $studentId);
                            });
                        },
                        false: static function ($query): void {
                            $studentId = Auth::id();
                            if (! $studentId) {
                                return;
                            }
                            $query->whereDoesntHave('submissions', static function ($q) use ($studentId): void {
                                $q->where('student_id', $studentId);
                            });
                        },
                        blank: static function ($query): void {
                            //
                        },
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('submit')
                    ->label(__('Submit'))
                    ->icon(Heroicon::PaperAirplane)
                    ->visible(function (Evaluation $record): bool {
                        $studentId = Auth::id();
                        if (! $studentId) {
                            return false;
                        }

                        return ! $record->submissions()
                            ->where('student_id', $studentId)
                            ->exists();
                    })
                    ->url(fn (Evaluation $record): string => SubmissionResource::getUrl('create', [
                        'evaluation_id' => $record->getKey(),
                    ])),
            ])
            ->toolbarActions([
                //
            ])
            ->defaultSort('due_at', 'asc')
            ->paginationPageOptions([10, 25, 50]);
    }
}
