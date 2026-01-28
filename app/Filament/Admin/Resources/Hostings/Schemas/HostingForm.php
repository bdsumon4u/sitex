<?php

namespace App\Filament\Admin\Resources\Hostings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class HostingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('organization_id')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),
                Select::make('server_id')
                    ->relationship('server', 'name')
                    ->hint(str('Select an **Organization** before.')->inlineMarkdown()->toHtmlString())
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('domain')
                    ->required(),
                TextInput::make('username')
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->required(),
                TextInput::make('token')
                    ->required(),
                TextInput::make('site_limit')
                    ->required()
                    ->numeric()
                    ->default(1),
            ])
            ->columns(3);
    }
}
