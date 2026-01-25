<?php

namespace App\Filament\Resources\Hostings\Pages;

use App\Filament\Resources\Hostings\HostingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewHosting extends ViewRecord
{
    protected static string $resource = HostingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // EditAction::make(),
        ];
    }
}
