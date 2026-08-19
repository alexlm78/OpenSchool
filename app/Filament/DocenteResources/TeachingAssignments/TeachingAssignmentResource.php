<?php

declare(strict_types=1);

namespace App\Filament\DocenteResources\TeachingAssignments;

use App\Filament\DocenteResources\DocenteResource;
use App\Filament\DocenteResources\TeachingAssignments\Pages\CreateTeachingAssignment;
use App\Filament\DocenteResources\TeachingAssignments\Pages\EditTeachingAssignment;
use App\Filament\DocenteResources\TeachingAssignments\Pages\ListTeachingAssignments;
use App\Filament\DocenteResources\TeachingAssignments\Schemas\TeachingAssignmentForm;
use App\Filament\DocenteResources\TeachingAssignments\Tables\TeachingAssignmentsTable;
use App\Models\TeachingAssignment;
use App\Models\User;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TeachingAssignmentResource extends DocenteResource
{
    protected static ?string $model = TeachingAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getModelLabel(): string
    {
        return __('Teaching Assignment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Teaching Assignments');
    }

    public static function form(Schema $schema): Schema
    {
        return TeachingAssignmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeachingAssignmentsTable::configure($table)
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                if ($user instanceof User && $user->hasRole('teacher')) {
                    $query->where('teacher_id', $user->getAuthIdentifier());
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
            'index' => ListTeachingAssignments::route('/'),
            'create' => CreateTeachingAssignment::route('/create'),
            'edit' => EditTeachingAssignment::route('/{record}/edit'),
        ];
    }
}
