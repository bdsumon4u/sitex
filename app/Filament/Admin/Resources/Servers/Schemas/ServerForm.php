<?php

namespace App\Filament\Admin\Resources\Servers\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ServerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('ip')
                    ->label('IP')
                    ->required(),
                TextInput::make('username'),
                TextInput::make('password'),
                TextInput::make('ftp_port')
                    ->numeric()
                    ->default(21),
                TextInput::make('ssh_port')
                    ->numeric()
                    ->default(22),
                Textarea::make('endpoint')
                    ->columnSpan(3),
                Textarea::make('token')
                    ->columnSpan(3),
            ])
            ->dense()
            ->columns(6);
    }
}
