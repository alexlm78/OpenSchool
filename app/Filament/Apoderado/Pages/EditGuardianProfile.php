<?php

declare(strict_types=1);

namespace App\Filament\Apoderado\Pages;

use App\Models\Guardian;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;

final class EditGuardianProfile extends Page implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-user';

    protected string $view = 'filament.pages.edit-guardian-profile';

    public static function getNavigationLabel(): string
    {
        return __('navigation.apoderado_profile');
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
        $profile = $user->guardianProfile;
        if (! $profile instanceof Guardian) {
            abort(404);
        }
        Gate::authorize('update', $profile);
        $this->form->fill([
            'name' => $user->getAttributeValue('name'),
            'email' => $user->getAttributeValue('email'),
            'relationship' => $profile->getAttributeValue('relationship'),
            'phone' => $profile->getAttributeValue('phone'),
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('auth.profile_name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label(__('auth.profile_email'))
                    ->email()
                    ->required()
                    ->maxLength(255),
                Select::make('relationship')
                    ->label(__('guardian.relationship'))
                    ->required()
                    ->options([
                        'mother' => __('guardian.relationship_mother'),
                        'father' => __('guardian.relationship_father'),
                        'guardian' => __('guardian.relationship_legal'),
                        'other' => __('guardian.relationship_other'),
                    ]),
                TextInput::make('phone')
                    ->label(__('guardian.phone'))
                    ->tel()
                    ->required()
                    ->maxLength(50),
            ])
            ->statePath('data');
    }

    public function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('general.save'))
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
        $profile = $user->guardianProfile;
        if (! $profile instanceof Guardian) {
            abort(404);
        }
        Gate::authorize('update', $profile);
        $state = $this->form->getState();
        $user->update([
            'name' => (string) $state['name'],
            'email' => (string) $state['email'],
        ]);
        $profile->update([
            'relationship' => (string) $state['relationship'],
            'phone' => (string) $state['phone'],
        ]);
        FilamentNotification::make()
            ->success()
            ->title(__('auth.profile_updated'))
            ->send();
    }
}
