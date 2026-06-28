<?php

namespace App\Filament\DocenteResources\Evaluations\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class EvaluationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('course_offering_id')
                    ->required()
                    ->relationship(
                        'courseOffering',
                        'id',
                        function (Builder $query): Builder {
                            $user = Auth::user();
                            if (! $user instanceof \App\Models\User) {
                                return $query->whereRaw('1 = 0');
                            }

                            if ($user->hasRole('admin')) {
                                return $query;
                            }

                            if (! $user->hasRole('teacher')) {
                                return $query->whereRaw('1 = 0');
                            }

                            return $query->whereExists(function (\Illuminate\Database\Query\Builder $q) use ($user) {
                                $q->from('teaching_assignments')
                                    ->whereColumn('teaching_assignments.course_offering_id', 'course_offerings.id')
                                    ->where('teaching_assignments.teacher_id', $user->getAuthIdentifier());
                            });
                        },
                    )
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn (\App\Models\CourseOffering $record): string => trim(sprintf(
                        'Oferta #%d - Periodo %s - Curso %s - Sección %s',
                        $record->id,
                        (string) $record->academic_period_id,
                        (string) $record->course_template_id,
                        $record->section_name ?? '-',
                    ))),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('max_score')
                    ->required()
                    ->numeric()
                    ->default(100),
                TextInput::make('weight')
                    ->required()
                    ->numeric()
                    ->default(1),
                DateTimePicker::make('due_at'),
                DateTimePicker::make('published_at'),
                Toggle::make('allow_late_submission')
                    ->required()
                    ->default(false),
                TextInput::make('late_penalty_percent')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('late_until'),
                TextInput::make('file_requirements'),
            ]);
    }
}
