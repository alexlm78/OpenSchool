<?php

declare(strict_types=1);

namespace App\Filament\Alumno\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

final class Notifications extends Page implements HasTable
{
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationLabel = 'navigation.notifications';

    protected string $view = 'filament.alumno.pages.notifications';

    public static function getNavigationBadge(): ?string
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null) {
            return null;
        }

        $count = $user->unreadNotifications()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user === null) {
            return null;
        }

        return $user->unreadNotifications()->count() > 0 ? 'danger' : null;
    }

    public static function canAccess(): bool
    {
        return Auth::check() && Auth::user()?->hasRole('student');
    }

    public function getHeading(): string
    {
        return __('navigation.notifications_title');
    }

    public function getSubheading(): ?string
    {
        return __('navigation.notifications_subheading');
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
                    ->where('notifiable_id', $userId !== null ? (int) $userId : -1),
            )
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('read_at')
                    ->label(__('notifications.status'))
                    ->formatStateUsing(static function ($state): string {
                        return $state === null ? __('notifications.unread') : __('notifications.read');
                    })
                    ->badge()
                    ->color(static fn (string $state): string => $state === __('notifications.unread') ? 'danger' : 'gray')
                    ->sortable(),
                TextColumn::make('data.title')
                    ->label(__('notifications.title'))
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
                        $dot = "<span style=\"display:inline-block;width:10px;height:10px;border-radius:999px;background:{$color};margin-right:8px;vertical-align:middle;\"></span>";
                        $title = (string) ($state ?? __('general.notification'));
                        $isUnread = $record->getAttributeValue('read_at') === null;

                        return new HtmlString($dot.($isUnread ? "<strong>{$title}</strong>" : $title));
                    })
                    ->limit(50)
                    ->sortable(false),
                TextColumn::make('data.summary')
                    ->label(__('notifications.summary'))
                    ->limit(60)
                    ->tooltip(static fn (DatabaseNotification $r): string => (string) ($r->getAttribute('data')['summary'] ?? ''))
                    ->sortable(false),
                TextColumn::make('data.category')
                    ->label(__('notifications.category'))
                    ->badge()
                    ->color(static function ($state): string {
                        return match ((string) $state) {
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
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('read_at')
                    ->label(__('notifications.filter_status'))
                    ->placeholder(__('notifications.filter_all'))
                    ->trueLabel(__('notifications.filter_unread'))
                    ->falseLabel(__('notifications.filter_read'))
                    ->queries(
                        true: static fn (Builder $q): Builder => $q->whereNull('read_at'),
                        false: static fn (Builder $q): Builder => $q->whereNotNull('read_at'),
                        blank: static fn (Builder $q): Builder => $q,
                    ),
            ])
            ->headerActions([
                Action::make('mark_all_read')
                    ->label(__('notifications.mark_all_read'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function () use ($user): void {
                        if ($user === null) {
                            return;
                        }
                        \Gate::authorize('markAllAsRead', DatabaseNotification::class);
                        $user->unreadNotifications()->update(['read_at' => now()]);
                        FilamentNotification::make()
                            ->success()
                            ->title(__('notifications.marked_all_read'))
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_read')
                    ->label(__('notifications.mark_read'))
                    ->icon('heroicon-o-check')
                    ->hidden(fn (DatabaseNotification $n): bool => $n->getAttributeValue('read_at') !== null)
                    ->requiresConfirmation()
                    ->action(function (DatabaseNotification $n) use ($user): void {
                        \Gate::authorize('markAsRead', $n);
                        if ($user === null || (int) $n->getAttributeValue('notifiable_id') !== (int) $user->getKey()) {
                            return;
                        }
                        $n->markAsRead();
                        FilamentNotification::make()
                            ->success()
                            ->title(__('notifications.marked_read'))
                            ->send();
                    }),
                Tables\Actions\Action::make('view_entity')
                    ->label(__('notifications.go_to'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (DatabaseNotification $n): ?string => isset($n->getAttribute('data')['action_url']) ? url((string) $n->getAttribute('data')['action_url']) : null)
                    ->openUrlInNewTab()
                    ->hidden(fn (DatabaseNotification $n): bool => empty($n->getAttribute('data')['action_url'] ?? '')),
            ])
            ->paginationPageOptions([10, 25, 50]);
    }
}
