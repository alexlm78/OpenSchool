<?php

namespace App\Filament\Resources\AssignmentDetails;

use App\Filament\AdminResource;
use App\Filament\Resources\AssignmentDetails\Pages\CreateAssignmentDetails;
use App\Filament\Resources\AssignmentDetails\Pages\EditAssignmentDetails;
use App\Filament\Resources\AssignmentDetails\Pages\ListAssignmentDetails;
use App\Filament\Resources\AssignmentDetails\Schemas\AssignmentDetailsForm;
use App\Filament\Resources\AssignmentDetails\Tables\AssignmentDetailsTable;
use App\Models\AssignmentDetails;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AssignmentDetailsResource extends AdminResource
{
    protected static ?string $model = AssignmentDetails::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getModelLabel(): string
    {
        return __('Assignment Details');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Assignment Details');
    }

    public static function form(Schema $schema): Schema
    {
        return AssignmentDetailsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssignmentDetailsTable::configure($table);
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
            'index' => ListAssignmentDetails::route('/'),
            'create' => CreateAssignmentDetails::route('/create'),
            'edit' => EditAssignmentDetails::route('/{record}/edit'),
        ];
    }
}
