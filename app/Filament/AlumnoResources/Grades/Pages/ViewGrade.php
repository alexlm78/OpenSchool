<?php

declare(strict_types=1);

namespace App\Filament\AlumnoResources\Grades\Pages;

use App\Filament\AlumnoResources\Grades\GradeResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Schema;

class ViewGrade extends ViewRecord
{
    protected static string $resource = GradeResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Grade Details'))
                    ->schema([
                        TextEntry::make('evaluation.title')
                            ->label(__('Evaluation')),
                        TextEntry::make('evaluation.courseOffering.courseTemplate.name')
                            ->label(__('Course')),
                        TextEntry::make('evaluation.courseOffering.section_name')
                            ->label(__('Section')),
                        TextEntry::make('evaluation.courseOffering.academicPeriod.name')
                            ->label(__('Period')),
                        TextEntry::make('score')
                            ->label(__('Score'))
                            ->numeric()
                            ->badge()
                            ->color(static function (mixed $state): string {
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
                        TextEntry::make('evaluation.weight')
                            ->label(__('Weight'))
                            ->numeric(),
                        TextEntry::make('grader.name')
                            ->label(__('Graded By (Teacher)')),
                        TextEntry::make('grader.email')
                            ->label(__('Teacher Email')),
                        TextEntry::make('feedback')
                            ->label(__('Feedback'))
                            ->columnSpanFull(),
                        TextEntry::make('evaluation.due_at')
                            ->label(__('Evaluation Due At'))
                            ->dateTime(),
                        TextEntry::make('created_at')
                            ->label(__('Graded At'))
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
