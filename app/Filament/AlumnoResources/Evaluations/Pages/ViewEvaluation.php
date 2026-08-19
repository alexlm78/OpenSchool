<?php

declare(strict_types=1);

namespace App\Filament\AlumnoResources\Evaluations\Pages;

use App\Filament\AlumnoResources\Evaluations\EvaluationResource;
use App\Filament\AlumnoResources\Submissions\SubmissionResource;
use App\Models\Evaluation;
use Filament\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

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
                Section::make(__('Your Submission'))
                    ->schema([
                        TextEntry::make('submission_info')
                            ->label(__('Submission'))
                            ->state(function (Evaluation $record): string {
                                $studentId = Auth::id();
                                if (! $studentId) {
                                    return __('N/A');
                                }

                                $submission = $record->submissions()
                                    ->where('student_id', $studentId)
                                    ->with('submissionFiles')
                                    ->first();

                                if (! $submission) {
                                    return __('No submission yet.');
                                }

                                $lines = [];
                                $lines[] = __('Status').': '.ucfirst($submission->status);
                                $lines[] = __('Submitted At').': '.($submission->submitted_at?->format('Y-m-d H:i') ?? __('N/A'));
                                $lines[] = __('Attempt').": {$submission->attempt}";
                                $lines[] = __('Late').': '.($submission->late_flag ? __('Yes') : __('No'));
                                $fileCount = $submission->submissionFiles->count();
                                $lines[] = __('Files').": {$fileCount}";

                                return implode("\n", $lines);
                            })
                            ->columnSpanFull(),
                        TextEntry::make('grade_info')
                            ->label(__('Grade'))
                            ->state(function (Evaluation $record): string {
                                $studentId = Auth::id();
                                if (! $studentId) {
                                    return __('N/A');
                                }

                                $grade = $record->grades()
                                    ->where('student_id', $studentId)
                                    ->first();

                                if (! $grade) {
                                    return __('Not graded yet.');
                                }

                                $lines = [];
                                $maxScore = (float) $record->max_score;
                                $score = $grade->score;
                                $lines[] = __('Score').": {$score}/{$maxScore}";
                                if ($grade->feedback) {
                                    $lines[] = __('Feedback').": {$grade->feedback}";
                                }

                                return implode("\n", $lines);
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('submit')
                ->label(__('Submit'))
                ->icon(Heroicon::PaperAirplane)
                ->visible(function (): bool {
                    $record = $this->record;
                    $studentId = Auth::id();
                    if (! $studentId || ! $record instanceof Evaluation) {
                        return false;
                    }

                    return ! $record->submissions()
                        ->where('student_id', $studentId)
                        ->exists();
                })
                ->url(fn (): string => SubmissionResource::getUrl('create', [
                    'evaluation_id' => $this->record->getKey(),
                ])),
        ];
    }
}
