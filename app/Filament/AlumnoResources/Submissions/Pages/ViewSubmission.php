<?php

declare(strict_types=1);

namespace App\Filament\AlumnoResources\Submissions\Pages;

use App\Filament\AlumnoResources\Submissions\SubmissionResource;
use App\Models\Submission;
use App\Models\SubmissionFile;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ViewSubmission extends ViewRecord
{
    protected static string $resource = SubmissionResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Submission Details'))
                    ->schema([
                        TextEntry::make('evaluation.title')
                            ->label(__('Evaluation')),
                        TextEntry::make('evaluation.courseOffering.courseTemplate.name')
                            ->label(__('Course')),
                        TextEntry::make('status')
                            ->label(__('Status'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'draft' => 'gray',
                                'submitted' => 'info',
                                'graded' => 'success',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => ucfirst($state)),
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
                        TextEntry::make('comment')
                            ->label(__('Comment'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('Attached Files'))
                    ->schema([
                        RepeatableEntry::make('submissionFiles')
                            ->label(__('Files'))
                            ->schema([
                                TextEntry::make('file_name')
                                    ->label(__('File Name')),
                                TextEntry::make('file_type')
                                    ->label(__('Type')),
                                TextEntry::make('file_size')
                                    ->label(__('Size'))
                                    ->formatStateUsing(fn (?int $state): string => $state !== null ? self::formatBytes($state) : __('N/A')),
                                TextEntry::make('download')
                                    ->label(__('Download'))
                                    ->state(fn (SubmissionFile $record): string => '')
                                    ->suffixAction(function (SubmissionFile $record) {
                                        return Action::make('download')
                                            ->label(__('Download'))
                                            ->icon(Heroicon::ArrowDownTray)
                                            ->url(route('submission-files.download', $record), shouldOpenInNewTab: true);
                                    }),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ]),
                Section::make(__('Grade'))
                    ->schema([
                        TextEntry::make('gradeInfo')
                            ->label(__('Result'))
                            ->state(function (Submission $record): string {
                                $grade = $record->evaluation->grades()
                                    ->where('student_id', $record->student_id)
                                    ->first();

                                if (! $grade) {
                                    return __('Not graded yet.');
                                }

                                $maxScore = (float) $record->evaluation->max_score;
                                $lines = [];
                                $lines[] = __('Score').": {$grade->score}/{$maxScore}";
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
            //
        ];
    }

    private static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, \count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }
}
