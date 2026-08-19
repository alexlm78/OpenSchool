<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $localeOptions = collect(config('app.available_locales', []))
            ->mapWithKeys(fn (array $meta, string $code): array => [$code => "{$meta['flag']} {$meta['native']}"])
            ->toArray();

        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required(),
                TextInput::make('email')
                    ->label(__('Email Address'))
                    ->email()
                    ->required(),
                Select::make('locale')
                    ->label(__('Default Language'))
                    ->options($localeOptions)
                    ->searchable(false)
                    ->placeholder(__('Use system default'))
                    ->helperText(__('Preferred language for this user when logging in.')),
                DateTimePicker::make('email_verified_at')
                    ->label(__('Email Verified At')),
                TextInput::make('password')
                    ->label(__('Password'))
                    ->password()
                    ->required(static fn (string $context): bool => $context === 'create'),
                Select::make('school_id')
                    ->label(__('School'))
                    ->relationship('school', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
