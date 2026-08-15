<?php

namespace App\Filament\Resources\TeachingAssignments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class TeachingAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('school_id')
                    ->label(__('School'))
                    ->relationship('school', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live(),
                Select::make('course_offering_id')
                    ->label(__('Course Offering'))
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
                    ->getOptionLabelFromRecordUsing(
                        static fn ($record): string => trim(sprintf(
                            __('Offer #%d - Period %s - Course %s - Section %s'),
                            (int) $record->id,
                            (string) ($record->academicPeriod?->name ?? (string) $record->academic_period_id),
                            (string) ($record->courseTemplate?->name ?? (string) $record->course_template_id),
                            $record->section_name ?? '-',
                        )),
                    )
                    ->required(),
                Select::make('teacher_id')
                    ->label(__('Teacher'))
                    ->relationship(
                        name: 'teacher',
                        titleAttribute: 'name',
                        modifyQueryUsing: static function (\Illuminate\Database\Eloquent\Builder $q, callable $get) {
                            $q->when(
                                filled($get('school_id')),
                                fn (\Illuminate\Database\Eloquent\Builder $q) => $q->where(function ($sub) use ($get) {
                                    $schoolId = $get('school_id');
                                    $sub->where('school_id', $schoolId)
                                        ->orWhereNull('school_id');
                                }),
                            );

                            $q->whereHas(
                                'roles',
                                fn (\Illuminate\Database\Eloquent\Builder $roleQ) => $roleQ->whereIn('name', ['super_admin', 'admin', 'teacher']),
                            );
                        },
                    )
                    ->searchable(['name', 'email'])
                    ->preload()
                    ->required(),
            ]);
    }
}
