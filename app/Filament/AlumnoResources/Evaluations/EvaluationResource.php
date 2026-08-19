<?php

declare(strict_types=1);

namespace App\Filament\AlumnoResources\Evaluations;

use App\Filament\AlumnoResource;
use App\Filament\AlumnoResources\Evaluations\Pages\ListEvaluations;
use App\Filament\AlumnoResources\Evaluations\Pages\ViewEvaluation;
use App\Filament\AlumnoResources\Evaluations\Tables\EvaluationsTable;
use App\Models\Evaluation;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class EvaluationResource extends AlumnoResource
{
    protected static ?string $model = Evaluation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Clipboard;

    public static function getNavigationLabel(): string
    {
        return __('My Evaluations');
    }

    public static function getModelLabel(): string
    {
        return __('Evaluation');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Evaluations');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }

    public static function table(Table $table): Table
    {
        return EvaluationsTable::configure($table)
            ->modifyQueryUsing(function (Builder $query) {
                $studentId = static::currentStudentUserId();
                if ($studentId !== null) {
                    $query->whereExists(function (QueryBuilder $q) use ($studentId) {
                        $q->from('enrollments')
                            ->whereColumn('enrollments.course_offering_id', 'evaluations.course_offering_id')
                            ->where('enrollments.student_id', $studentId)
                            ->where('enrollments.status', 'active');
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
            'view' => ViewEvaluation::route('/{record}'),
        ];
    }
}
