<?php

declare(strict_types=1);

namespace App\Filament\Apoderado\Widgets;

use App\Models\User;
use App\Support\LinkedGuardianStudents;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

final class ApoderadoNotificationsWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected static ?string $heading = 'widgets.recent_notifications_title';

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
        $linkedUserIds = $linked['userIds'];
        $linkedProfileIds = $linked['profileIds'];
        $anyLinked = $linkedUserIds !== [] || $linkedProfileIds !== [];
        $safeIds = $anyLinked ? array_values(array_unique([...$linkedUserIds, ...$linkedProfileIds])) : [-1];

        return $table
            ->query(
                DatabaseNotification::query()
                    ->where('notifiable_type', User::class)
                    ->whereIn('notifiable_id', $safeIds)
                    ->orderByDesc('created_at')
                    ->limit(5),
            )
            ->columns([
                TextColumn::make('read_at')
                    ->label(__('notifications.status'))
                    ->formatStateUsing(static function ($state): string {
                        return $state === null ? __('notifications.unread') : __('notifications.read');
                    })
                    ->badge()
                    ->color(static fn (string $state): string => $state === __('notifications.unread') ? 'danger' : 'gray')
                    ->sortable(false),
                TextColumn::make('data_student')
                    ->label(__('widgets.apoderado_notif_student'))
                    ->getStateUsing(static fn (DatabaseNotification $r): string => (string) ($r->getAttribute('data')['student_name'] ?? (string) ($r->getAttributeValue('notifiable_id') ?? '')))
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(false),
                TextColumn::make('data_title')
                    ->label(__('notifications.title'))
                    ->getStateUsing(static fn (DatabaseNotification $r): string => (string) ($r->getAttribute('data')['title'] ?? __('general.notification')))
                    ->formatStateUsing(static function (string $state, DatabaseNotification $record): HtmlString {
                        $level = (string) ($record->getAttribute('data')['level'] ?? 'default');
                        $colors = [
                            'success' => '#22c55e',
                            'warning' => '#f59e0b',
                            'danger' => '#ef4444',
                            'info' => '#3b82f6',
                            'default' => '#64748b',
                        ];
                        $color = $colors[$level] ?? $colors['default'];
                        $dot = "<span style=\"display:inline-block;width:8px;height:8px;border-radius:999px;background:{$color};margin-right:6px;vertical-align:middle;\"></span>";
                        $title = $state;
                        $isUnread = $record->getAttributeValue('read_at') === null;

                        return new HtmlString($dot.($isUnread ? "<strong>{$title}</strong>" : $title));
                    })
                    ->limit(40)
                    ->sortable(false),
                TextColumn::make('data_category')
                    ->label(__('notifications.category'))
                    ->getStateUsing(static fn (DatabaseNotification $r): string => (string) ($r->getAttribute('data')['category'] ?? ''))
                    ->badge()
                    ->color(static function (string $state): string {
                        return match ($state) {
                            'grade' => 'success',
                            'evaluation' => 'info',
                            'submission' => 'warning',
                            'enrollment' => 'primary',
                            default => 'gray',
                        };
                    })
                    ->sortable(false),
                TextColumn::make('created_at')
                    ->label(__('notifications.received_at'))
                    ->dateTime('M d, Y H:i')
                    ->sortable(false),
            ])
            ->actions([
                Action::make('view_entity')
                    ->label(__('notifications.go_to'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (DatabaseNotification $n): ?string => isset($n->getAttribute('data')['action_url']) ? url((string) $n->getAttribute('data')['action_url']) : null)
                    ->openUrlInNewTab()
                    ->hidden(fn (DatabaseNotification $n): bool => empty($n->getAttribute('data')['action_url'] ?? '')),
            ])
            ->paginated(false);
    }
}
