<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\Hostings\HostingResource;
use App\Filament\Widgets\HostingsRenewingWithinWeekWidget as BaseHostingsRenewingWithinWeekWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HostingsRenewingWithinWeekWidget extends BaseHostingsRenewingWithinWeekWidget
{
    public function table(Table $table): Table
    {
        $table = parent::table($table);

        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label(__('Team'))
                    ->sortable()
                    ->searchable(),
                ...$table->getColumns(),
            ])
            ->recordUrl(fn ($record) => HostingResource::getUrl('view', ['record' => $record]));
    }
}
