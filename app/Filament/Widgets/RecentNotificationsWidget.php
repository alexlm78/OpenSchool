<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

final class RecentNotificationsWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected static ?string $heading = 'widgets.notifications_title';

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
        $userId = $user?->getKey();

        return $table
            ->query(
                DatabaseNotification::query()
                    ->where('notifiable_type', User::class)
                    ->where('notifiable_id', $userId !== null ? (int) $userId : -1)
                    ->orderByDesc('created_at')
                    ->limit(5),
            )
            ->columns([
                TextColumn::make('data.title')
                    ->label(__('widgets.notifications_title_col'))
                    ->formatStateUsing(static function ($state, DatabaseNotification $record): HtmlString {
                        $level = (string) ($record->getAttribute('data')['level'] ?? 'default');
                        $colors = [
                            'success' => '#22c55e',
                            'warning' => '#f59e0b',
                            'danger' => '#ef4444',
                            'info' => '#3b82f6',
                            'default' => '#64748b',
                        ];
                        $color = $colors[$level] ?? $colors['default'];
                        $dot = "<span style=\"display:inline-block;width:8px;height:8px;border-radius:999px;background:{$color};margin-right:6px;\"></span>";
                        $title = (string) ($state ?? __('general.notification'));
                        $isUnread = $record->getAttributeValue('read_at') === null;

                        return new HtmlString(
                            $dot.($isUnread ? "<strong>{$title}</strong>" : $title),
                        );
                    })
                    ->sortable(false),
                TextColumn::make('data.summary')
                    ->label(__('widgets.notifications_summary'))
                    ->limit(50)
                    ->tooltip(fn (DatabaseNotification $r): string => (string) ($r->getAttribute('data')['summary'] ?? ''))
                    ->sortable(false),
                TextColumn::make('created_at')
                    ->label(__('widgets.notifications_time'))
                    ->since()
                    ->sortable(false),
            ])
            ->headerActions([
                Action::make('mark_all_read')
                    ->label(__('widgets.notifications_mark_all'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (): void {
                        /** @var User|null $user */
                        $user = Auth::user();
                        if ($user === null) {
                            return;
                        }
                        \Gate::authorize('markAllAsRead', DatabaseNotification::class);
                        $user->unreadNotifications->markAsRead();
                        Notification::make()
                            ->success()
                            ->title(__('widgets.notifications_marked'))
                            ->send();
                    }),
            ])
            ->paginated(false);
    }
}
