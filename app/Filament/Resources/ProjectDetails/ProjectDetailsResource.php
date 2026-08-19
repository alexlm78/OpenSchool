<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProjectDetails;

use App\Filament\AdminResource;
use App\Filament\Resources\ProjectDetails\Pages\CreateProjectDetails;
use App\Filament\Resources\ProjectDetails\Pages\EditProjectDetails;
use App\Filament\Resources\ProjectDetails\Pages\ListProjectDetails;
use App\Filament\Resources\ProjectDetails\Schemas\ProjectDetailsForm;
use App\Filament\Resources\ProjectDetails\Tables\ProjectDetailsTable;
use App\Models\ProjectDetails;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProjectDetailsResource extends AdminResource
{
    protected static ?string $model = ProjectDetails::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getModelLabel(): string
    {
        return __('Project Details');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Project Details');
    }

    public static function form(Schema $schema): Schema
    {
        return ProjectDetailsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectDetailsTable::configure($table);
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
            'index' => ListProjectDetails::route('/'),
            'create' => CreateProjectDetails::route('/create'),
            'edit' => EditProjectDetails::route('/{record}/edit'),
        ];
    }
}
