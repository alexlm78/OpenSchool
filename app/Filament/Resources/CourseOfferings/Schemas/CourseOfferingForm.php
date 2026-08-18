<?php

namespace App\Filament\Resources\CourseOfferings\Schemas;

use App\Models\CourseOffering;
use App\Models\School;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

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
                    ->default(0)
                    ->helperText(static function (callable $get): string {
                        $schoolId = $get('school_id');
                        if (! is_numeric($schoolId)) {
                            return __('Select a school first to view the valid capacity range.');
                        }
                        $school = School::query()->find((int) $schoolId);
                        if (! $school instanceof School) {
                            return __('Select a school first to view the valid capacity range.');
                        }

                        return __('School capacity policy: :policy. 0 = unlimited (if allowed).', [
                            'policy' => $school->capacityValidationMessage(),
                        ]);
                    })
                    ->rules([
                        static function (callable $get): \Closure {
                            return static function (string $attribute, $value, \Closure $fail) use ($get): void {
                                $schoolId = $get('school_id');
                                if (! is_numeric($schoolId)) {
                                    return;
                                }
                                $school = School::query()->find((int) $schoolId);
                                if (! $school instanceof School) {
                                    return;
                                }
                                $capacity = (int) $value;
                                if (! $school->isCourseOfferingCapacityValid($capacity)) {
                                    $fail(__('Invalid capacity for this school (:policy).', [
                                        'policy' => $school->capacityValidationMessage(),
                                    ]));
                                }
                            };
                        },
                    ]),
                TextInput::make('section_name')
                    ->label(__('Section Name'))
                    ->placeholder(__('e.g. A, B, Sección 1, Morning')),
            ]);
    }
}
