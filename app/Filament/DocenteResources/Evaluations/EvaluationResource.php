<?php

namespace App\Filament\DocenteResources\Evaluations;

use App\Filament\DocenteResources\DocenteResource;
use App\Filament\DocenteResources\Evaluations\Pages\CreateEvaluation;
use App\Filament\DocenteResources\Evaluations\Pages\EditEvaluation;
use App\Filament\DocenteResources\Evaluations\Pages\ListEvaluations;
use App\Filament\DocenteResources\Evaluations\Schemas\EvaluationForm;
use App\Filament\DocenteResources\Evaluations\Tables\EvaluationsTable;
use App\Models\Evaluation;
use App\Models\TeachingAssignment;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;

class EvaluationResource extends DocenteResource
{
    protected static ?string $model = Evaluation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return EvaluationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EvaluationsTable::configure($table)
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();
                if ($user instanceof \App\Models\User && $user->hasRole('teacher')) {
                    $query->whereExists(function (QueryBuilder $q) use ($user) {
                        $q->from((new TeachingAssignment())->getTable())
                            ->whereColumn('teaching_assignments.course_offering_id', 'evaluations.course_offering_id')
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
            'index' => ListEvaluations::route('/'),
            'create' => CreateEvaluation::route('/create'),
            'edit' => EditEvaluation::route('/{record}/edit'),
        ];
    }
}
