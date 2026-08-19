<?php

namespace App\Filament\ApoderadoResources\Enrollments;

use App\Filament\ApoderadoResource;
use App\Filament\ApoderadoResources\Enrollments\Pages\ListEnrollments;
use App\Filament\ApoderadoResources\Enrollments\Pages\ViewEnrollment;
use App\Filament\ApoderadoResources\Enrollments\Tables\EnrollmentsTable;
use App\Models\Enrollment;
use App\Models\Student;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EnrollmentResource extends ApoderadoResource
{
    protected static ?string $model = Enrollment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::AcademicCap;

    public static function getNavigationLabel(): string
    {
        return __('Enrollments');
    }

    public static function getModelLabel(): string
    {
        return __('Enrollment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Enrollments');
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

        return EnrollmentsTable::configure($table, $studentFilterOptions)
            ->modifyQueryUsing(function (Builder $query) use ($studentUserIds) {
                $query->whereIn('student_id', $studentUserIds);
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
            'index' => ListEnrollments::route('/'),
            'view' => ViewEnrollment::route('/{record}'),
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
}
