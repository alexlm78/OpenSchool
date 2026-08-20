<?php

declare(strict_types=1);

namespace App\Filament\Apoderado\Pages;

use App\Models\User;
use App\Support\LinkedGuardianStudents;
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
use Spatie\Permission\PermissionRegistrar;

final class Notifications extends Page implements HasTable
{
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationLabel = 'navigation.notifications';

    protected string $view = 'filament.apoderado.pages.notifications';

    public static function getNavigationBadge(): ?string
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user instanceof User) {
            return null;
        }

        $linked = LinkedGuardianStudents::resolveForUser($user);
        $userIds = $linked['userIds'];
        $profileIds = $linked['profileIds'];
        if ($userIds === [] && $profileIds === []) {
            return null;
        }
        $safeIds = array_values(array_unique([...$userIds, ...$profileIds]));

        /** @var int $count */
        $count = DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->whereIn('notifiable_id', $safeIds)
            ->whereNull('read_at')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return self::getNavigationBadge() !== null ? 'danger' : null;
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return false;
        }

        $schoolId = filter_var($user->getAttributeValue('school_id'), \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (\is_int($schoolId)) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($schoolId);
        }

        return $user->hasRole('guardian');
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
        $linkedIds = [];
        if ($user instanceof User) {
            $linked = LinkedGuardianStudents::resolveForUser($user);
            $userIds = $linked['userIds'];
            $profileIds = $linked['profileIds'];
            $linkedIds = array_values(array_unique([...$userIds, ...$profileIds]));
        }

        return $table
            ->query(
                DatabaseNotification::query()
                    ->where('notifiable_type', User::class)
                    ->whereIn('notifiable_id', $linkedIds !== [] ? $linkedIds : [-1]),
            )
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('notifiable_id')
                    ->label(__('notifications.student'))
                    ->getStateUsing(static fn (DatabaseNotification $record): string => (string) ($record->getAttribute('data')['student_name'] ?? (string) ($record->getAttributeValue('notifiable_id') ?? '')))
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(false),
                TextColumn::make('read_at')
                    ->label(__('notifications.status'))
                    ->formatStateUsing(static function ($state): string {
                        return $state === null ? __('notifications.unread') : __('notifications.read');
                    })
                    ->badge()
                    ->color(static fn (string $state): string => $state === __('notifications.unread') ? 'danger' : 'gray')
                    ->sortable(),
                TextColumn::make('data->title')
                    ->label(__('notifications.title'))
                    ->formatStateUsing(static function ($state, DatabaseNotification $record): HtmlString {
                        $data = $record->getAttribute('data');
                        $level = (string) (($data['level'] ?? 'default'));
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
                TextColumn::make('data->summary')
                    ->label(__('notifications.summary'))
                    ->limit(60)
                    ->tooltip(static fn (DatabaseNotification $r): string => (string) (($r->getAttribute('data')['summary']) ?? ''))
                    ->sortable(false),
                TextColumn::make('data->category')
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
                    ->action(function () use ($user, $linkedIds): void {
                        if (! $user instanceof User || $linkedIds === []) {
                            return;
                        }
                        \Gate::authorize('markAllAsRead', DatabaseNotification::class);
                        DatabaseNotification::query()
                            ->where('notifiable_type', User::class)
                            ->whereIn('notifiable_id', $linkedIds)
                            ->whereNull('read_at')
                            ->update(['read_at' => now()]);
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
                    ->action(function (DatabaseNotification $n) use ($linkedIds): void {
                        \Gate::authorize('markAsRead', $n);
                        $notifiableId = (int) $n->getAttributeValue('notifiable_id');
                        if (! \in_array($notifiableId, $linkedIds, true)) {
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
