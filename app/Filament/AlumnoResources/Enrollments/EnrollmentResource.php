<?php

declare(strict_types=1);

namespace App\Filament\AlumnoResources\Enrollments;

use App\Filament\AlumnoResource;
use App\Filament\AlumnoResources\Enrollments\Pages\ListEnrollments;
use App\Filament\AlumnoResources\Enrollments\Pages\ViewEnrollment;
use App\Filament\AlumnoResources\Enrollments\Tables\EnrollmentsTable;
use App\Models\Enrollment;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EnrollmentResource extends AlumnoResource
{
    protected static ?string $model = Enrollment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BookOpen;

    public static function getNavigationLabel(): string
    {
        return __('My Courses');
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
        return EnrollmentsTable::configure($table)
            ->modifyQueryUsing(function (Builder $query) {
                $studentId = static::currentStudentUserId();
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
            'index' => ListEnrollments::route('/'),
            'view' => ViewEnrollment::route('/{record}'),
        ];
    }
}
