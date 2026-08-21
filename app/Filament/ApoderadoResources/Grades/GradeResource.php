<?php

declare(strict_types=1);

namespace App\Filament\ApoderadoResources\Grades;

use App\Filament\ApoderadoResource;
use App\Filament\ApoderadoResources\Grades\Pages\ListGrades;
use App\Filament\ApoderadoResources\Grades\Pages\ViewGrade;
use App\Filament\ApoderadoResources\Grades\Tables\GradesTable;
use App\Models\CourseOffering;
use App\Models\Evaluation;
use App\Models\Grade;
use App\Models\Student;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class GradeResource extends ApoderadoResource
{
    protected static ?string $model = Grade::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChartBar;

    public static function getNavigationLabel(): string
    {
        return __('Grades');
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
        $studentUserIds = static::linkedStudentUserIds();
        $studentProfileIds = static::linkedStudentProfileIds();
        $studentFilterOptions = self::buildStudentFilterOptions($studentProfileIds);
        $evaluationOptions = self::buildEvaluationOptions($studentUserIds);
        $courseOfferingOptions = self::buildCourseOfferingOptions($studentUserIds);

        return GradesTable::configure($table, $studentFilterOptions, $evaluationOptions, $courseOfferingOptions, $studentUserIds)
            ->modifyQueryUsing(function (Builder $query) use ($studentUserIds) {
                $query->whereIn('student_id', $studentUserIds !== [] ? $studentUserIds : [-1]);
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
     * @param  array<int, int>  $studentProfileIds
     * @return array<string, string>
     */
    protected static function buildStudentFilterOptions(array $studentProfileIds): array
    {
        if ($studentProfileIds === []) {
            return [];
        }

        return Student::query()
            ->with('user:id,name')
            ->whereIn('id', $studentProfileIds)
            ->get()
            ->mapWithKeys(static function (Student $student): array {
                $name = (string) ($student->user->name ?? __('Unknown Student'));
                if (! empty($student->student_id)) {
                    $name .= " ({$student->student_id})";
                }

                return [(string) $student->getKey() => $name];
            })
            ->all();
    }

    /**
     * @param  array<int, int>  $studentUserIds
     * @return array<string, string>
     */
    protected static function buildEvaluationOptions(array $studentUserIds): array
    {
        if (empty($studentUserIds)) {
            return [];
        }

        return Evaluation::query()
            ->whereExists(function (QueryBuilder $q) use ($studentUserIds) {
                $q->from('enrollments')
                    ->whereColumn('enrollments.course_offering_id', 'evaluations.course_offering_id')
                    ->whereIn('enrollments.student_id', $studentUserIds)
                    ->where('enrollments.status', 'active');
            })
            ->orderBy('due_at', 'desc')
            ->get()
            ->mapWithKeys(static function (Evaluation $evaluation): array {
                $title = (string) $evaluation->title;
                $maxScore = (string) $evaluation->max_score;

                return [(string) $evaluation->id => "{$title} (Max: {$maxScore})"];
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
