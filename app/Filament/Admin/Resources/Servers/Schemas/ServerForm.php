<?php

namespace App\Filament\Admin\Resources\Servers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ServerForm
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
                TextInput::make('name')
                    ->required(),
                TextInput::make('ip')
                    ->required(),
                TextInput::make('username'),
                Textarea::make('endpoint')
                    ->columnSpan(2),
                Textarea::make('token')
                    ->columnSpan(2),
            ])
            ->columns(4);
    }
}
