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
                    ->required()
                    ->relationship('student', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('course_offering_id')
                    ->required()
                    ->relationship('courseOffering', 'id')
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn (\App\Models\CourseOffering $record): string => trim(sprintf(
                        'Oferta #%d - Periodo %s - Curso %s - Sección %s',
                        $record->id,
                        (string) $record->academic_period_id,
                        (string) $record->course_template_id,
                        $record->section_name ?? '-',
                    ))),
                Select::make('status')
                    ->required()
                    ->options([
                        'active' => 'active',
                        'completed' => 'completed',
                        'dropped' => 'dropped',
                    ])
                    ->default('active'),
                DatePicker::make('enrolled_at')
                    ->default(now()),
                DatePicker::make('completed_at'),
            ]);
    }
}
