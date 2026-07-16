<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->required(),
                Select::make('locale')
                    ->required()
                    ->options(config('locales.supported'))
                    ->default(config('app.fallback_locale'))
                    ->searchable(),
                Select::make('timezone')
                    ->required()
                    ->options(array_combine($timezones = \DateTimezone::listIdentifiers(), $timezones))
                    ->default('UTC')
                    ->searchable(),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn ($operation) => $operation === 'create')
                    ->dehydrated(fn ($state) => filled($state)),
            ]);
    }
}
