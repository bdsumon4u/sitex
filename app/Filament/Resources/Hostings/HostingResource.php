<?php

namespace App\Filament\Resources\Hostings;

use App\Filament\Resources\Hostings\Pages\CreateHosting;
use App\Filament\Resources\Hostings\Pages\EditHosting;
use App\Filament\Resources\Hostings\Pages\ListHostings;
use App\Filament\Resources\Hostings\Pages\ViewHosting;
use App\Filament\Resources\Hostings\Schemas\HostingForm;
use App\Filament\Resources\Hostings\Schemas\HostingInfolist;
use App\Filament\Resources\Hostings\Tables\HostingsTable;
use App\Models\Hosting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HostingResource extends Resource
{
    protected static ?string $model = Hosting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'domain';

    public static function form(Schema $schema): Schema
    {
        return HostingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return HostingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HostingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHostings::route('/'),
            // 'create' => CreateHosting::route('/create'),
            'view' => ViewHosting::route('/{record}'),
            // 'edit' => EditHosting::route('/{record}/edit'),
        ];
    }
}
