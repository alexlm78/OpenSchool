<?php

declare(strict_types=1);

namespace App\Filament\Apoderado\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

final class ChangeGuardianPassword extends Page implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-lock-closed';

    protected string $view = 'filament.pages.change-guardian-password';

    public static function getNavigationLabel(): string
    {
        return __('navigation.apoderado_password');
    }

    public static function canAccess(): bool
    {
        /** @var User|null $user */
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

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }
        $schoolId = filter_var($user->getAttributeValue('school_id'), \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (\is_int($schoolId)) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($schoolId);
        }
        if (! $user->hasRole('guardian')) {
            abort(403);
        }
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                TextInput::make('current_password')
                    ->label(__('passwords.current_password'))
                    ->password()
                    ->revealable()
                    ->required()
                    ->currentPassword(),
                TextInput::make('password')
                    ->label(__('passwords.new_password'))
                    ->password()
                    ->revealable()
                    ->required()
                    ->confirmed()
                    ->minLength(8),
                TextInput::make('password_confirmation')
                    ->label(__('passwords.confirm_password'))
                    ->password()
                    ->revealable()
                    ->required(),
            ])
            ->statePath('data');
    }

    public function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('passwords.change_password'))
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }
        $state = $this->form->getState();
        $currentHash = (string) $user->getAttributeValue('password');
        if (! Hash::check((string) $state['current_password'], $currentHash)) {
            throw ValidationException::withMessages([
                'data.current_password' => [__('passwords.current_incorrect')],
            ]);
        }
        $user->update([
            'password' => Hash::make((string) $state['password']),
        ]);
        FilamentNotification::make()
            ->success()
            ->title(__('passwords.changed'))
            ->send();
        $this->form->fill();
    }
}
