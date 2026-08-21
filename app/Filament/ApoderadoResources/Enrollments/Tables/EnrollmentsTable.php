<?php

declare(strict_types=1);

namespace App\Filament\ApoderadoResources\Enrollments\Tables;

use App\Models\Student;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EnrollmentsTable
{
    /**
     * @param  array<string, string>  $studentFilterOptions
     * @param  array<int, int>  $studentUserIds
     */
    public static function configure(Table $table, array $studentFilterOptions = [], array $studentUserIds = []): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.name')
                    ->label(__('Student'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('courseOffering.courseTemplate.name')
                    ->label(__('Course'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('courseOffering.section_name')
                    ->label(__('Section'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('courseOffering.academicPeriod.name')
                    ->label(__('Period'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'completed' => 'info',
                        'dropped' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('enrolled_at')
                    ->label(__('Enrolled At'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('completed_at')
                    ->label(__('Completed At'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('student')
                    ->label(__('Filtrar por Estudiante'))
                    ->options($studentFilterOptions)
                    ->modifyQueryUsing(function (Builder $query, $state) use ($studentUserIds): Builder {
                        $value = $state['value'] ?? null;
                        if (empty($value)) {
                            return $query;
                        }

                        $profileId = (int) $value;
                        $userId = collect($studentUserIds)->search($profileId, true);
                        $target = $userId === false ? null : (int) $userId;
                        if ($target === null && $profileId > 0) {
                            $found = Student::query()
                                ->where('id', $profileId)
                                ->value('user_id');
                            $target = $found !== null ? (int) $found : null;
                        }

                        return $target !== null ? $query->where('student_id', $target) : $query;
                    }),
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        'active' => __('Active'),
                        'completed' => __('Completed'),
                        'dropped' => __('Dropped'),
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                //
            ])
            ->defaultSort('enrolled_at', 'desc')
            ->paginationPageOptions([10, 25, 50]);
    }
}
