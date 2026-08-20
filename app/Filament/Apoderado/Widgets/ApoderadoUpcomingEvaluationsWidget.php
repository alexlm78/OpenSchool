<?php

declare(strict_types=1);

namespace App\Filament\Apoderado\Widgets;

use App\Filament\ApoderadoResources\Evaluations\EvaluationResource;
use App\Models\Evaluation;
use App\Models\Submission;
use App\Models\User;
use App\Support\LinkedGuardianStudents;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

final class ApoderadoUpcomingEvaluationsWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected static ?string $heading = 'widgets.upcoming_title';

    protected ?string $pollingInterval = null;

    protected array|string|int $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::check() && Auth::user()?->hasRole('guardian');
    }

    public function table(Table $table): Table
    {
        /** @var User|null $user */
        $user = Auth::user();
        $linked = $user instanceof User ? LinkedGuardianStudents::resolveForUser($user) : ['profileIds' => [], 'userIds' => []];
        $profileIds = $linked['profileIds'];

        return $table
            ->query(
                Evaluation::query()
                    ->with(['courseOffering.courseTemplate'])
                    ->where('published_at', '<=', now())
                    ->where('due_at', '>=', now())
                    ->whereExists(function ($q) use ($profileIds): void {
                        $q->from('enrollments')
                            ->whereColumn('enrollments.course_offering_id', 'evaluations.course_offering_id')
                            ->whereIn('enrollments.student_id', $profileIds !== [] ? $profileIds : [-1])
                            ->where('enrollments.status', 'active');
                    })
                    ->orderBy('due_at', 'asc')
                    ->limit(5),
            )
            ->columns([
                TextColumn::make('id')
                    ->label(__('widgets.apoderado_student_name'))
                    ->getStateUsing(static function (Evaluation $record) use ($profileIds): string {
                        if ($profileIds === []) {
                            return '';
                        }
                        $names = \DB::table('enrollments')
                            ->join('students', 'students.id', '=', 'enrollments.student_id')
                            ->join('users', 'users.id', '=', 'students.user_id')
                            ->whereColumn('enrollments.course_offering_id', 'evaluations.course_offering_id')
                            ->whereIn('enrollments.student_id', $profileIds)
                            ->where('enrollments.status', 'active')
                            ->pluck('users.name')
                            ->unique()
                            ->values()
                            ->all();

                        return implode(', ', $names);
                    })
                    ->limit(30)
                    ->tooltip(function (Evaluation $r) use ($profileIds): string {
                        $names = \DB::table('enrollments')
                            ->join('students', 'students.id', '=', 'enrollments.student_id')
                            ->join('users', 'users.id', '=', 'students.user_id')
                            ->where('enrollments.course_offering_id', (int) $r->getAttributeValue('course_offering_id'))
                            ->whereIn('enrollments.student_id', $profileIds !== [] ? $profileIds : [-1])
                            ->where('enrollments.status', 'active')
                            ->pluck('users.name')
                            ->implode(', ');

                        return (string) rtrim((string) str_replace(',', ', ', $names), ', ');
                    }),
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
                    ->getStateUsing(static function (Evaluation $record) use ($profileIds): string {
                        if ($profileIds === []) {
                            return '';
                        }
                        $total = \DB::table('enrollments')
                            ->whereColumn('course_offering_id', 'evaluations.course_offering_id')
                            ->whereIn('student_id', $profileIds)
                            ->where('status', 'active')
                            ->count();
                        $submitted = Submission::query()
                            ->where('evaluation_id', (int) $record->getKey())
                            ->whereIn('student_id', $profileIds)
                            ->where('status', 'submitted')
                            ->count();
                        if ($total <= 0) {
                            return '';
                        }
                        if ($submitted === 0) {
                            return __('widgets.upcoming_pending');
                        }
                        if ($submitted >= $total) {
                            return __('widgets.upcoming_submitted');
                        }

                        return "{$submitted}/{$total}";
                    })
                    ->badge()
                    ->color(static function (string $state): string {
                        if ($state === '' || $state === __('widgets.upcoming_pending')) {
                            return 'warning';
                        }
                        if ($state === __('widgets.upcoming_submitted')) {
                            return 'success';
                        }

                        return 'info';
                    })
                    ->sortable(false),
            ])
            ->actions([
                Action::make('view_evaluation')
                    ->label(__('widgets.upcoming_view'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Evaluation $record): string => EvaluationResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->paginated(false);
    }
}
