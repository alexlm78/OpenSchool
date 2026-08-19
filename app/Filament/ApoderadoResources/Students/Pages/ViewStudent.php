<?php

declare(strict_types=1);

namespace App\Filament\ApoderadoResources\Students\Pages;

use App\Filament\ApoderadoResources\Students\StudentResource;
use App\Models\Enrollment;
use App\Models\Evaluation;
use App\Models\Grade;
use App\Models\Student;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Student Tabs')
                    ->tabs([
                        Tabs\Tab::make(__('Details'))
                            ->schema([
                                Section::make(__('Student Details'))
                                    ->schema([
                                        TextEntry::make('student_id')
                                            ->label(__('Matrícula')),
                                        TextEntry::make('user.name')
                                            ->label(__('Student Name')),
                                        TextEntry::make('user.email')
                                            ->label(__('Email')),
                                        TextEntry::make('date_of_birth')
                                            ->label(__('Date of Birth'))
                                            ->date(),
                                        TextEntry::make('gender')
                                            ->label(__('Gender')),
                                        TextEntry::make('phone')
                                            ->label(__('Phone')),
                                        TextEntry::make('address')
                                            ->label(__('Address'))
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                                Section::make(__('Guardian Relationship'))
                                    ->schema([
                                        RepeatableEntry::make('guardians')
                                            ->label(__('Guardians'))
                                            ->schema([
                                                TextEntry::make('user.name')
                                                    ->label(__('Guardian Name')),
                                                TextEntry::make('user.email')
                                                    ->label(__('Email')),
                                                TextEntry::make('relationship')
                                                    ->label(__('Relationship')),
                                                TextEntry::make('phone')
                                                    ->label(__('Phone')),
                                            ])
                                            ->columns(4)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tabs\Tab::make(__('Enrollments'))
                            ->schema([
                                Section::make(__('Course Enrollments'))
                                    ->schema([
                                        RepeatableEntry::make('enrollmentList')
                                            ->label(__('Enrollments'))
                                            ->state(function (Student $record) {
                                                return Enrollment::query()
                                                    ->with([
                                                        'courseOffering.courseTemplate',
                                                        'courseOffering.academicPeriod',
                                                    ])
                                                    ->where('student_id', $record->user_id)
                                                    ->get();
                                            })
                                            ->schema([
                                                TextEntry::make('courseOffering.courseTemplate.name')
                                                    ->label(__('Course')),
                                                TextEntry::make('courseOffering.section_name')
                                                    ->label(__('Section')),
                                                TextEntry::make('courseOffering.academicPeriod.name')
                                                    ->label(__('Period')),
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
                                                    ->date(),
                                            ])
                                            ->columns(5)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tabs\Tab::make(__('Evaluations'))
                            ->schema([
                                Section::make(__('Evaluations by Course'))
                                    ->schema([
                                        RepeatableEntry::make('evaluationList')
                                            ->label(__('Evaluations'))
                                            ->state(function (Student $record) {
                                                $studentUserId = $record->user_id;

                                                return Evaluation::query()
                                                    ->with([
                                                        'courseOffering.courseTemplate',
                                                        'courseOffering.academicPeriod',
                                                    ])
                                                    ->whereExists(function ($q) use ($studentUserId) {
                                                        $q->from('enrollments')
                                                            ->whereColumn('enrollments.course_offering_id', 'evaluations.course_offering_id')
                                                            ->where('enrollments.student_id', $studentUserId)
                                                            ->where('enrollments.status', 'active');
                                                    })
                                                    ->orderBy('due_at', 'desc')
                                                    ->get();
                                            })
                                            ->schema([
                                                TextEntry::make('courseOffering.courseTemplate.name')
                                                    ->label(__('Course')),
                                                TextEntry::make('title')
                                                    ->label(__('Title')),
                                                TextEntry::make('due_at')
                                                    ->label(__('Due At'))
                                                    ->dateTime(),
                                                TextEntry::make('max_score')
                                                    ->label(__('Max Score'))
                                                    ->numeric(),
                                                TextEntry::make('evaluationStatus')
                                                    ->label(__('Status'))
                                                    ->badge()
                                                    ->state(function (Evaluation $evaluation) use ($record) {
                                                        $studentUserId = $record->user_id;

                                                        $hasSubmission = $evaluation->submissions()
                                                            ->where('student_id', $studentUserId)
                                                            ->exists();

                                                        if (! $hasSubmission) {
                                                            return 'not_yet_submitted';
                                                        }

                                                        $hasGrade = $evaluation->grades()
                                                            ->where('student_id', $studentUserId)
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
                                            ->columns(5)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tabs\Tab::make(__('Grades'))
                            ->schema([
                                Section::make(__('Grades Summary'))
                                    ->schema([
                                        RepeatableEntry::make('gradeList')
                                            ->label(__('Grades'))
                                            ->state(function (Student $record) {
                                                return Grade::query()
                                                    ->with([
                                                        'evaluation.courseOffering.courseTemplate',
                                                        'grader',
                                                    ])
                                                    ->where('student_id', $record->user_id)
                                                    ->latest()
                                                    ->get();
                                            })
                                            ->schema([
                                                TextEntry::make('evaluation.courseOffering.courseTemplate.name')
                                                    ->label(__('Course')),
                                                TextEntry::make('evaluation.title')
                                                    ->label(__('Evaluation')),
                                                TextEntry::make('score')
                                                    ->label(__('Score'))
                                                    ->numeric()
                                                    ->badge()
                                                    ->color(function (string $state): string {
                                                        $num = (float) $state;
                                                        if ($num >= 70) {
                                                            return 'success';
                                                        }
                                                        if ($num >= 50) {
                                                            return 'warning';
                                                        }

                                                        return 'danger';
                                                    }),
                                                TextEntry::make('evaluation.max_score')
                                                    ->label(__('Max Score'))
                                                    ->numeric(),
                                                TextEntry::make('grader.name')
                                                    ->label(__('Teacher')),
                                                TextEntry::make('created_at')
                                                    ->label(__('Graded At'))
                                                    ->dateTime(),
                                            ])
                                            ->columns(6)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
