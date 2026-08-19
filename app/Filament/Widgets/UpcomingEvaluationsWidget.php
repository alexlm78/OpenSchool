<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Evaluation;
use App\Models\Submission;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

final class UpcomingEvaluationsWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected static ?string $heading = 'widgets.upcoming_title';

    protected ?string $pollingInterval = null;

    protected array|string|int $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::check() && Auth::user()?->hasRole('student');
    }

    public function table(Table $table): Table
    {
        /** @var User|null $user */
        $user = Auth::user();
        $studentId = $user?->getKey();

        return $table
            ->query(
                Evaluation::query()
                    ->with(['courseOffering.courseTemplate'])
                    ->where('published_at', '<=', now())
                    ->where('due_at', '>=', now())
                    ->whereExists(function (Builder $q) use ($studentId): void {
                        $q->from('enrollments')
                            ->whereColumn('enrollments.course_offering_id', 'evaluations.course_offering_id')
                            ->where('enrollments.student_id', $studentId !== null ? (int) $studentId : -1)
                            ->where('enrollments.status', 'active');
                    })
                    ->orderBy('due_at', 'asc')
                    ->limit(5),
            )
            ->columns([
                TextColumn::make('courseOffering.courseTemplate.name')
                    ->label(__('widgets.upcoming_course'))
                    ->sortable(false),
                TextColumn::make('title')
                    ->label(__('widgets.upcoming_evaluation'))
                    ->limit(40)
                    ->tooltip(fn (Evaluation $r): string => (string) $r->getAttributeValue('title'))
                    ->sortable(false),
                TextColumn::make('due_at')
                    ->label(__('widgets.upcoming_due'))
                    ->dateTime('M d, Y H:i')
                    ->color(static function (Evaluation $record): string {
                        $due = $record->getAttributeValue('due_at');
                        if ($due === null) {
                            return 'gray';
                        }
                        $hoursLeft = now()->diffInHours($due, false);

                        return match (true) {
                            $hoursLeft <= 24 => 'danger',
                            $hoursLeft <= 72 => 'warning',
                            default => 'info',
                        };
                    })
                    ->badge()
                    ->sortable(false),
                TextColumn::make('status')
                    ->label(__('widgets.upcoming_status'))
                    ->getStateUsing(static function (Evaluation $record) use ($studentId): string {
                        if ($studentId === null) {
                            return '';
                        }
                        $has = Submission::query()
                            ->where('evaluation_id', (int) $record->getKey())
                            ->where('student_id', (int) $studentId)
                            ->where('status', 'submitted')
                            ->exists();

                        return $has ? __('widgets.upcoming_submitted') : __('widgets.upcoming_pending');
                    })
                    ->badge()
                    ->color(static fn (string $state): string => $state === __('widgets.upcoming_submitted') ? 'success' : 'warning')
                    ->sortable(false),
            ])
            ->paginated(false);
    }
}
