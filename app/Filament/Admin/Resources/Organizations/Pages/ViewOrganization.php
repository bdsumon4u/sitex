<?php

namespace App\Filament\Admin\Resources\Organizations\Pages;

use App\Filament\Admin\Resources\Organizations\OrganizationResource;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewOrganization extends ViewRecord
{
    protected static string $resource = OrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Organization Details')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('ulid')
                            ->label('ID'),
                        TextEntry::make('phone')
                            ->placeholder('No phone'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
                Section::make('Users')
                    ->schema([
                        RepeatableEntry::make('users')
                            ->schema([
                                TextEntry::make('name')
                                    ->weight('bold'),
                                TextEntry::make('email')
                                    ->label('Email address'),
                                TextEntry::make('email_verified_at')
                                    ->dateTime()
                                    ->placeholder('Not verified')
                                    ->label('Email verified'),
                                TextEntry::make('created_at')
                                    ->dateTime()
                                    ->label('User created'),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
