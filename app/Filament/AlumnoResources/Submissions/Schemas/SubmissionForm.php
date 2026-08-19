<?php

declare(strict_types=1);

namespace App\Filament\AlumnoResources\Submissions\Schemas;

use App\Models\Evaluation;
use App\Tenancy\TenantContext;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SubmissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('evaluation_id')
                    ->label(__('Evaluation'))
                    ->options(function (): array {
                        $studentId = Auth::id();
                        $schoolId = app(TenantContext::class)->getSchoolId();

                        if (! $studentId || ! $schoolId) {
                            return [];
                        }

                        return Evaluation::query()
                            ->where('evaluations.school_id', $schoolId)
                            ->whereExists(function ($q) use ($studentId) {
                                $q->from('enrollments')
                                    ->whereColumn('enrollments.course_offering_id', 'evaluations.course_offering_id')
                                    ->where('enrollments.student_id', $studentId)
                                    ->where('enrollments.status', 'active');
                            })
                            ->pluck('title', 'id')
                            ->toArray();
                    })
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Set $set, $state) {
                        self::recalculateLateFlag($set, $state, null);
                    }),
                DateTimePicker::make('submitted_at')
                    ->label(__('Submitted At'))
                    ->default(now())
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                        self::recalculateLateFlag($set, $get('evaluation_id'), $state);
                    }),
                Select::make('status')
                    ->label(__('Status'))
                    ->options([
                        'draft' => __('Draft'),
                        'submitted' => __('Submitted'),
                    ])
                    ->default('submitted')
                    ->required(),
                TextInput::make('attempt')
                    ->label(__('Attempt'))
                    ->numeric()
                    ->default(1)
                    ->required()
                    ->minValue(1),
                Toggle::make('late_flag')
                    ->label(__('Late Flag'))
                    ->disabled()
                    ->default(false),
                Textarea::make('comment')
                    ->label(__('Comment'))
                    ->columnSpanFull()
                    ->rows(3),
                Repeater::make('submissionFiles')
                    ->label(__('Submission Files'))
                    ->relationship()
                    ->schema([
                        FileUpload::make('file_path')
                            ->label(__('File'))
                            ->disk('local')
                            ->visibility('private')
                            ->required()
                            ->storeFileNamesIn('file_name')
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                if (\is_string($state)) {
                                    $disk = Storage::disk('local');
                                    $fullPath = $disk->path($state);
                                    if (file_exists($fullPath)) {
                                        $set('file_size', filesize($fullPath));
                                        $set('file_type', mime_content_type($fullPath) ?: 'application/octet-stream');
                                    }
                                }
                            })
                            ->columnSpanFull(),
                        Hidden::make('file_name'),
                        Hidden::make('file_type'),
                        Hidden::make('file_size'),
                    ])
                    ->defaultItems(1)
                    ->minItems(0)
                    ->columnSpanFull(),
            ]);
    }

    private static function recalculateLateFlag(Set $set, $evaluationId, $submittedAt): void
    {
        $lateFlag = false;

        if ($evaluationId && $submittedAt) {
            $evaluation = Evaluation::find($evaluationId);
            if ($evaluation) {
                $evaluationable = $evaluation->evaluationable;
                $allowLate = $evaluationable && isset($evaluationable->allow_late_submission) && $evaluationable->allow_late_submission;

                if (! $allowLate && $evaluation->due_at) {
                    $submittedDate = \is_string($submittedAt) ? new \DateTime($submittedAt) : $submittedAt;
                    if ($submittedDate > $evaluation->due_at) {
                        $lateFlag = true;
                    }
                }
            }
        }

        $set('late_flag', $lateFlag);
    }
}
