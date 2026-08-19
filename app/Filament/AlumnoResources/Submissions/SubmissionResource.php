<?php

declare(strict_types=1);

namespace App\Filament\AlumnoResources\Submissions;

use App\Filament\AlumnoResource;
use App\Filament\AlumnoResources\Submissions\Pages\CreateSubmission;
use App\Filament\AlumnoResources\Submissions\Pages\ListSubmissions;
use App\Filament\AlumnoResources\Submissions\Pages\ViewSubmission;
use App\Filament\AlumnoResources\Submissions\Schemas\SubmissionForm;
use App\Filament\AlumnoResources\Submissions\Tables\SubmissionsTable;
use App\Models\Submission;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SubmissionResource extends AlumnoResource
{
    protected static ?string $model = Submission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::PaperAirplane;

    public static function getNavigationLabel(): string
    {
        return __('My Submissions');
    }

    public static function getModelLabel(): string
    {
        return __('Submission');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Submissions');
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return SubmissionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubmissionsTable::configure($table)
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
            'index' => ListSubmissions::route('/'),
            'create' => CreateSubmission::route('/create'),
            'view' => ViewSubmission::route('/{record}'),
        ];
    }
}
