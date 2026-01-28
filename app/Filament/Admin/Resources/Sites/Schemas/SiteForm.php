<?php

namespace App\Filament\Admin\Resources\Sites\Schemas;

use App\Enums\SiteStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SiteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('parent_id')
                    ->relationship('parent', 'name')
                    ->searchable(),
                Select::make('hosting_id')
                    ->relationship('hosting', 'domain')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('domain')
                    ->required(),
                TextInput::make('directory')
                    ->required(),
                TextInput::make('email_username')
                    ->email()
                    ->required(),
                TextInput::make('email_password')
                    ->email()
                    ->password()
                    ->required(),
                TextInput::make('database_name')
                    ->required(),
                TextInput::make('database_user')
                    ->required(),
                TextInput::make('database_pass')
                    ->required(),
                Select::make('status')
                    ->options(SiteStatus::class)
                    ->searchable()
                    ->required(),
            ]);
    }
}
