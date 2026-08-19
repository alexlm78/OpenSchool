<?php

declare(strict_types=1);

namespace App\Filament\ApoderadoResources\Students;

use App\Filament\ApoderadoResource;
use App\Filament\ApoderadoResources\Students\Pages\ListStudents;
use App\Filament\ApoderadoResources\Students\Pages\ViewStudent;
use App\Filament\ApoderadoResources\Students\Tables\StudentsTable;
use App\Models\Student;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentResource extends ApoderadoResource
{
    protected static ?string $model = Student::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    public static function getNavigationLabel(): string
    {
        return __('My Students');
    }

    public static function getModelLabel(): string
    {
        return __('Student');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Students');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }

    public static function table(Table $table): Table
    {
        return StudentsTable::configure($table)
            ->modifyQueryUsing(function (Builder $query) {
                $studentUserIds = static::linkedStudentUserIds();
                $query->whereIn('user_id', $studentUserIds);
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
            'index' => ListStudents::route('/'),
            'view' => ViewStudent::route('/{record}'),
        ];
    }
}
