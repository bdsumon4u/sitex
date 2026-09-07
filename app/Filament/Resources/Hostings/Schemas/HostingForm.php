<?php

namespace App\Filament\Resources\Hostings\Schemas;

use Filament\Forms\Components\DatePicker;
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
                    ->label('Team')
                    ->relationship('organization', 'name')
                    ->required(),
                Select::make('server_id')
                    ->label('WHM')
                    ->relationship('server', 'name')
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
                DatePicker::make('renew_date')
                    ->label(__('Renew date'))
                    ->native(false)
                    ->displayFormat('M j, Y')
                    ->nullable(),
                TextInput::make('ssh_port')
                    ->required()
                    ->numeric()
                    ->default(22),
            ]);
    }
}
