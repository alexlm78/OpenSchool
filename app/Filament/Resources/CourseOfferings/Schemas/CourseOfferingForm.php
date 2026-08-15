<?php

namespace App\Filament\Resources\CourseOfferings\Schemas;

use App\Models\CourseOffering;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CourseOfferingForm
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
                Select::make('academic_period_id')
                    ->label(__('Academic Period'))
                    ->relationship(
                        name: 'academicPeriod',
                        titleAttribute: 'name',
                        modifyQueryUsing: static fn (\Illuminate\Database\Eloquent\Builder $q, callable $get) => $q
                            ->when(
                                filled($get('school_id')),
                                fn (\Illuminate\Database\Eloquent\Builder $q) => $q->where('school_id', $get('school_id')),
                            ),
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('course_template_id')
                    ->label(__('Course Template'))
                    ->relationship(
                        name: 'courseTemplate',
                        titleAttribute: 'name',
                        modifyQueryUsing: static fn (\Illuminate\Database\Eloquent\Builder $q, callable $get) => $q
                            ->when(
                                filled($get('school_id')),
                                fn (\Illuminate\Database\Eloquent\Builder $q) => $q->where('school_id', $get('school_id')),
                            ),
                    )
                    ->searchable(['name', 'code'])
                    ->preload()
                    ->getOptionLabelFromRecordUsing(
                        static fn ($record): string => trim(sprintf(
                            '[%s] %s',
                            (string) $record->code,
                            (string) $record->name,
                        )),
                    )
                    ->required(),
                TextInput::make('capacity')
                    ->label(__('Capacity'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                TextInput::make('section_name')
                    ->label(__('Section Name'))
                    ->placeholder(__('e.g. A, B, Sección 1, Morning')),
            ]);
    }
}
