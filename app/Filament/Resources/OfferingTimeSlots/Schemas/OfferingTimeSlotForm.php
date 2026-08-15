<?php

namespace App\Filament\Resources\OfferingTimeSlots\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class OfferingTimeSlotForm
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
                    ->getOptionLabelFromRecordUsing(
                        static fn ($record): string => trim(sprintf(
                            __(
                                'Offer #%d - Period %s - Course %s - Section %s',
                            ),
                            (int) $record->id,
                            (string) ($record->academicPeriod?->name ?? (string) $record->academic_period_id),
                            (string) ($record->courseTemplate?->name ?? (string) $record->course_template_id),
                            $record->section_name ?? '-',
                        )),
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('time_slot_id')
                    ->label(__('Time Slot'))
                    ->relationship(
                        name: 'timeSlot',
                        modifyQueryUsing: static fn (\Illuminate\Database\Eloquent\Builder $q, callable $get) => $q
                            ->when(
                                filled($get('school_id')),
                                fn (\Illuminate\Database\Eloquent\Builder $q) => $q->where('school_id', $get('school_id')),
                            ),
                    )
                    ->getOptionLabelFromRecordUsing(
                        static fn ($record): string => trim(sprintf(
                            '%s %s - %s',
                            (string) $record->day_of_week,
                            (string) $record->start_time,
                            (string) $record->end_time,
                        )),
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }
}
