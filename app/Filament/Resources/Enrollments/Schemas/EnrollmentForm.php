<?php

namespace App\Filament\Resources\Enrollments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class EnrollmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('student_id')
                    ->label(__('Student'))
                    ->required()
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('course_offering_id')
                    ->label(__('Course Offering'))
                    ->required()
                    ->relationship(
                        name: 'courseOffering',
                        modifyQueryUsing: static fn (\Illuminate\Database\Eloquent\Builder $q, callable $get) => $q
                            ->when(
                                filled($get('school_id')),
                                fn (\Illuminate\Database\Eloquent\Builder $q) => $q->where('school_id', $get('school_id')),
                            ),
                    )
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn (\App\Models\CourseOffering $record): string => trim(sprintf(
                        __('Offer #%d - Period %s - Course %s - Section %s'),
                        $record->id,
                        (string) ($record->academicPeriod?->name ?? (string) $record->academic_period_id),
                        (string) ($record->courseTemplate?->name ?? (string) $record->course_template_id),
                        $record->section_name ?? '-',
                    ))),
                Select::make('status')
                    ->label(__('Status'))
                    ->required()
                    ->options([
                        'active' => __('Active'),
                        'completed' => __('Completed'),
                        'dropped' => __('Dropped'),
                    ])
                    ->default('active'),
                DatePicker::make('enrolled_at')
                    ->label(__('Enrolled At'))
                    ->default(now()),
                DatePicker::make('completed_at')
                    ->label(__('Completed At')),
            ]);
    }
}
