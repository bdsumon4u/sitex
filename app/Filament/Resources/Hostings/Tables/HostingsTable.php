<?php

namespace App\Filament\Resources\Hostings\Tables;

use App\Models\Hosting;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class HostingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('sites'))
            ->defaultPaginationPageOption(25)
            ->recordClasses(fn (Hosting $record): string => $record->sites_count >= $record->site_limit ? 'bg-danger-50' : '')
            ->columns([
                TextColumn::make('server.name')
                    ->searchable(),
                TextColumn::make('domain')
                    ->searchable(),
                TextColumn::make('username')
                    ->searchable(),
                TextColumn::make('site_limit')
                    ->label('Sites')
                    ->formatStateUsing(function (Model $record, string $state) {
                        return $record->sites()->count().' / '.$state;
                    }),
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
                // EditAction::make(),
            ])
            ->toolbarActions([
                // BulkActionGroup::make([
                //     DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
