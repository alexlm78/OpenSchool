<?php

declare(strict_types=1);

namespace App\Filament\Resources\TeachingAssignments;

use App\Filament\AdminResource;
use App\Filament\Resources\TeachingAssignments\Pages\CreateTeachingAssignment;
use App\Filament\Resources\TeachingAssignments\Pages\EditTeachingAssignment;
use App\Filament\Resources\TeachingAssignments\Pages\ListTeachingAssignments;
use App\Filament\Resources\TeachingAssignments\Schemas\TeachingAssignmentForm;
use App\Filament\Resources\TeachingAssignments\Tables\TeachingAssignmentsTable;
use App\Models\TeachingAssignment;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TeachingAssignmentResource extends AdminResource
{
    protected static ?string $model = TeachingAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 50;

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
        return TeachingAssignmentsTable::configure($table);
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
