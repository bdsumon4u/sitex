<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\Hostings\HostingResource;
use App\Models\Hosting;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HostingsRenewingWithinWeekWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Hostings renewing within 7 days';

    public static function canView(): bool
    {
        return static::renewingWithinWeekQuery()->exists();
    }

    /**
     * @return Builder<Hosting>
     */
    protected static function renewingWithinWeekQuery(): Builder
    {
        $today = now()->startOfDay()->toDateString();
        $weekEnd = now()->addDays(7)->endOfDay()->toDateString();

        return Hosting::query()
            ->whereNotNull('renew_date')
            ->whereDate('renew_date', '>=', $today)
            ->whereDate('renew_date', '<=', $weekEnd);
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->query(
                static::renewingWithinWeekQuery()
                    ->with(['server'])
                    ->withCount('sites')
                    ->orderBy('renew_date')
            )
            ->columns([
                TextColumn::make('server.name')
                    ->label(__('Server'))
                    ->placeholder(__('Standalone'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('domain')
                    ->label(__('Domain'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('username')
                    ->label(__('Username'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('site_limit')
                    ->label(__('Sites'))
                    ->formatStateUsing(function (Model $record, string $state) {
                        return $record->sites_count.' / '.$state;
                    }),
                TextColumn::make('renew_date')
                    ->label(__('Renew date'))
                    ->date()
                    ->sortable(),
            ])
            ->recordUrl(fn ($record) => HostingResource::getUrl('view', ['record' => $record]))
            ->paginated([10, 25, 50]);
    }
}
