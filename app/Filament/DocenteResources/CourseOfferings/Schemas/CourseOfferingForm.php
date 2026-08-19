<?php

declare(strict_types=1);

namespace App\Filament\DocenteResources\CourseOfferings\Schemas;

use App\Models\School;
use App\Tenancy\TenantContext;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

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
                    ->default(static function (): ?int {
                        $id = app(TenantContext::class)->getSchoolId();

                        return \is_int($id) ? $id : null;
                    })
                    ->live(),
                Select::make('academic_period_id')
                    ->label(__('Academic Period'))
                    ->relationship(
                        name: 'academicPeriod',
                        titleAttribute: 'name',
                        modifyQueryUsing: static fn (Builder $q, callable $get) => $q
                            ->when(
                                filled($get('school_id')),
                                fn (Builder $q) => $q->where('school_id', $get('school_id')),
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
                        modifyQueryUsing: static fn (Builder $q, callable $get) => $q
                            ->when(
                                filled($get('school_id')),
                                fn (Builder $q) => $q->where('school_id', $get('school_id')),
                            ),
                    )
                    ->searchable(['name', 'code'])
                    ->preload()
                    ->getOptionLabelFromRecordUsing(
                        static fn ($record): string => trim(\sprintf(
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
                            $schoolId = app(TenantContext::class)->getSchoolId();
                        }
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
                                    $schoolId = app(TenantContext::class)->getSchoolId();
                                }
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
                    ->placeholder(__('e.g. A, B, Morning')),
            ]);
    }
}
