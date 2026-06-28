<?php

namespace App\Filament\DocenteResources\Submissions;

use App\Filament\DocenteResources\DocenteResource;
use App\Filament\DocenteResources\Submissions\Pages\CreateSubmission;
use App\Filament\DocenteResources\Submissions\Pages\EditSubmission;
use App\Filament\DocenteResources\Submissions\Pages\ListSubmissions;
use App\Filament\DocenteResources\Submissions\Schemas\SubmissionForm;
use App\Filament\DocenteResources\Submissions\Tables\SubmissionsTable;
use App\Models\Submission;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;

class SubmissionResource extends DocenteResource
{
    protected static ?string $model = Submission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SubmissionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubmissionsTable::configure($table)
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                if ($user instanceof \App\Models\User && $user->hasRole('teacher')) {
                    $query->whereExists(function (QueryBuilder $q) use ($user) {
                        $q->from('evaluations')
                            ->join('teaching_assignments', function ($join) {
                                $join->on('teaching_assignments.course_offering_id', '=', 'evaluations.course_offering_id');
                            })
                            ->whereColumn('evaluations.id', 'submissions.evaluation_id')
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
            'index' => ListSubmissions::route('/'),
            'create' => CreateSubmission::route('/create'),
            'edit' => EditSubmission::route('/{record}/edit'),
        ];
    }
}
