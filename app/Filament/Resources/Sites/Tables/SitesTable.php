<?php

namespace App\Filament\Resources\Sites\Tables;

use App\Filament\Resources\Sites\Tables\Actions\DeleteDomainAction;
use App\Filament\Resources\Sites\Tables\Actions\DeleteFilesAction;
use App\Filament\Resources\Sites\Tables\Actions\DeleteSiteAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SitesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('hosting.domain')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('domain')
                    ->url(fn ($record) => 'http://'.$record->domain)
                    ->label('Domain')
                    ->openUrlInNewTab()
                    ->iconPosition('after')
                    ->icon('heroicon-o-link')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('directory')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ActionGroup::make([
                    DeleteDomainAction::make(),
                    DeleteFilesAction::make(),
                    DeleteSiteAction::make(),
                ])->defaultColor(Color::Red),
            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
