<?php

declare(strict_types=1);

namespace App\Filament\AlumnoResources\Grades;

use App\Filament\AlumnoResource;
use App\Filament\AlumnoResources\Grades\Pages\ListGrades;
use App\Filament\AlumnoResources\Grades\Pages\ViewGrade;
use App\Filament\AlumnoResources\Grades\Tables\GradesTable;
use App\Models\CourseOffering;
use App\Models\Evaluation;
use App\Models\Grade;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class GradeResource extends AlumnoResource
{
    protected static ?string $model = Grade::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartBar;

    public static function getNavigationLabel(): string
    {
        return __('My Grades');
    }

    public static function getModelLabel(): string
    {
        return __('Grade');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Grades');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }

    public static function table(Table $table): Table
    {
        $studentId = static::currentStudentUserId();
        $evaluationOptions = self::buildEvaluationOptions($studentId);
        $courseOfferingOptions = self::buildCourseOfferingOptions($studentId);

        return GradesTable::configure($table, $evaluationOptions, $courseOfferingOptions)
            ->modifyQueryUsing(function (Builder $query) use ($studentId) {
                if ($studentId !== null) {
                    $query->where('student_id', $studentId);
                }
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
            'index' => ListGrades::route('/'),
            'view' => ViewGrade::route('/{record}'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function buildEvaluationOptions(?int $studentUserId): array
    {
        if ($studentUserId === null) {
            return [];
        }

        return Evaluation::query()
            ->whereExists(function (QueryBuilder $q) use ($studentUserId) {
                $q->from('enrollments')
                    ->whereColumn('enrollments.course_offering_id', 'evaluations.course_offering_id')
                    ->where('enrollments.student_id', $studentUserId)
                    ->where('enrollments.status', 'active');
            })
            ->orderBy('due_at', 'desc')
            ->get()
            ->mapWithKeys(static function (Evaluation $evaluation): array {
                $title = (string) $evaluation->getAttributeValue('title');
                $maxScore = (string) $evaluation->getAttributeValue('max_score');

                return [(string) $evaluation->getKey() => "{$title} (Max: {$maxScore})"];
            })
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected static function buildCourseOfferingOptions(?int $studentUserId): array
    {
        if ($studentUserId === null) {
            return [];
        }

        return CourseOffering::query()
            ->with(['courseTemplate:id,name', 'academicPeriod:id,name'])
            ->whereExists(function (QueryBuilder $q) use ($studentUserId) {
                $q->from('enrollments')
                    ->whereColumn('enrollments.course_offering_id', 'course_offerings.id')
                    ->where('enrollments.student_id', $studentUserId)
                    ->where('enrollments.status', 'active');
            })
            ->get()
            ->mapWithKeys(static function (CourseOffering $offering): array {
                $courseName = (string) ($offering->courseTemplate?->getAttributeValue('name') ?? __('Unknown Course'));
                $section = (string) $offering->getAttributeValue('section_name');
                $period = (string) ($offering->academicPeriod?->getAttributeValue('name') ?? '');
                $label = "{$courseName} - {$section}";
                if ($period !== '') {
                    $label .= " ({$period})";
                }

                return [(string) $offering->getKey() => $label];
            })
            ->all();
    }
}
