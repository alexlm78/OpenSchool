<?php

declare(strict_types=1);

namespace App\Filament\ApoderadoResources\Evaluations;

use App\Filament\ApoderadoResource;
use App\Filament\ApoderadoResources\Evaluations\Pages\ListEvaluations;
use App\Filament\ApoderadoResources\Evaluations\Pages\ViewEvaluation;
use App\Filament\ApoderadoResources\Evaluations\Tables\EvaluationsTable;
use App\Models\CourseOffering;
use App\Models\Evaluation;
use App\Models\Student;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class EvaluationResource extends ApoderadoResource
{
    protected static ?string $model = Evaluation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentList;

    public static function getNavigationLabel(): string
    {
        return __('Evaluations');
    }

    public static function getModelLabel(): string
    {
        return __('Evaluation');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Evaluations');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }

    public static function table(Table $table): Table
    {
        $studentUserIds = static::linkedStudentUserIds();
        $studentFilterOptions = self::buildStudentFilterOptions($studentUserIds);
        $courseOfferingOptions = self::buildCourseOfferingOptions($studentUserIds);

        return EvaluationsTable::configure($table, $studentFilterOptions, $courseOfferingOptions)
            ->modifyQueryUsing(function (Builder $query) use ($studentUserIds) {
                $query->whereExists(function (QueryBuilder $q) use ($studentUserIds) {
                    $q->from('enrollments')
                        ->whereColumn('enrollments.course_offering_id', 'evaluations.course_offering_id')
                        ->whereIn('enrollments.student_id', $studentUserIds)
                        ->where('enrollments.status', 'active');
                });
            });
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvaluations::route('/'),
            'view' => ViewEvaluation::route('/{record}'),
        ];
    }

    /**
     * @param  array<int, int>  $studentUserIds
     * @return array<string, string>
     */
    protected static function buildStudentFilterOptions(array $studentUserIds): array
    {
        if (empty($studentUserIds)) {
            return [];
        }

        return Student::query()
            ->with('user:id,name')
            ->whereIn('user_id', $studentUserIds)
            ->get()
            ->mapWithKeys(static function (Student $student): array {
                $name = (string) ($student->user->name ?? __('Unknown Student'));
                if (! empty($student->student_id)) {
                    $name .= " ({$student->student_id})";
                }

                return [(string) $student->user_id => $name];
            })
            ->all();
    }

    /**
     * @param  array<int, int>  $studentUserIds
     * @return array<string, string>
     */
    protected static function buildCourseOfferingOptions(array $studentUserIds): array
    {
        if (empty($studentUserIds)) {
            return [];
        }

        return CourseOffering::query()
            ->with(['courseTemplate:id,name', 'academicPeriod:id,name'])
            ->whereExists(function (QueryBuilder $q) use ($studentUserIds) {
                $q->from('enrollments')
                    ->whereColumn('enrollments.course_offering_id', 'course_offerings.id')
                    ->whereIn('enrollments.student_id', $studentUserIds)
                    ->where('enrollments.status', 'active');
            })
            ->get()
            ->mapWithKeys(static function (CourseOffering $offering): array {
                $courseName = (string) ($offering->courseTemplate->name ?? __('Unknown Course'));
                $section = (string) $offering->section_name;
                $period = (string) ($offering->academicPeriod->name ?? '');
                $label = "{$courseName} - {$section}";
                if ($period !== '') {
                    $label .= " ({$period})";
                }

                return [(string) $offering->id => $label];
            })
            ->all();
    }
}
