<?php

namespace App\Filament\Admin\Resources\Servers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ServerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('ip')
                    ->label('IP'),
                TextEntry::make('username')
                    ->placeholder('-'),
                TextEntry::make('password')
                    ->placeholder('-'),
                TextEntry::make('endpoint')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('token')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('ftp_port'),
                TextEntry::make('ssh_port'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
