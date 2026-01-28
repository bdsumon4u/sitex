<?php

namespace App\Filament\Admin\Resources\Sites\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SiteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('organization.name')
                    ->label('Organization'),
                TextEntry::make('parent.name')
                    ->label('Parent')
                    ->placeholder('-'),
                TextEntry::make('hosting.domain')
                    ->label('Hosting'),
                TextEntry::make('name'),
                TextEntry::make('domain'),
                TextEntry::make('directory'),
                TextEntry::make('email_username'),
                TextEntry::make('email_password'),
                TextEntry::make('database_name'),
                TextEntry::make('database_user'),
                TextEntry::make('database_pass'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
