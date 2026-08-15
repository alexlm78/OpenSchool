<?php

namespace App\Filament\DocenteResources\CourseOfferings;

use App\Filament\DocenteResources\DocenteResource;
use App\Filament\DocenteResources\CourseOfferings\Pages\CreateCourseOffering;
use App\Filament\DocenteResources\CourseOfferings\Pages\EditCourseOffering;
use App\Filament\DocenteResources\CourseOfferings\Pages\ListCourseOfferings;
use App\Filament\DocenteResources\CourseOfferings\Schemas\CourseOfferingForm;
use App\Filament\DocenteResources\CourseOfferings\Tables\CourseOfferingsTable;
use App\Models\CourseOffering;
use App\Models\TeachingAssignment;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;

class CourseOfferingResource extends DocenteResource
{
    protected static ?string $model = CourseOffering::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getModelLabel(): string
    {
        return __('Course Offering');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Course Offerings');
    }

    public static function form(Schema $schema): Schema
    {
        return CourseOfferingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CourseOfferingsTable::configure($table)
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                if ($user instanceof \App\Models\User && $user->hasRole('teacher')) {
                    $query->whereExists(function (QueryBuilder $q) use ($user) {
                        $q->from((new TeachingAssignment())->getTable())
                            ->whereColumn('teaching_assignments.course_offering_id', 'course_offerings.id')
                            ->where('teaching_assignments.teacher_id', $user->getAuthIdentifier());
                    });
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
            'index' => ListCourseOfferings::route('/'),
            'create' => CreateCourseOffering::route('/create'),
            'edit' => EditCourseOffering::route('/{record}/edit'),
        ];
    }
}
