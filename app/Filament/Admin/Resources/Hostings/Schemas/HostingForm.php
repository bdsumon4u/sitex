<?php

namespace App\Filament\Admin\Resources\Hostings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

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
                    ->relationship('server', 'name', function (Builder $query, Get $get) {
                        $query->where('organization_id', $get('organization_id'));
                    })
                    ->hint(str('Select an **Organization** to populate this drop-down list')->inlineMarkdown()->toHtmlString())
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
                TextInput::make('ssh_port')
                    ->required()
                    ->numeric()
                    ->default(22),
            ]);
    }
}
