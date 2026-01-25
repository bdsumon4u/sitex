<?php

namespace App\Filament\Resources\Sites\Pages;

use App\Filament\Resources\Sites\Pages\Actions\CopySshPubKeyAction;
use App\Filament\Resources\Sites\Pages\Actions\MultiSiteAction;
use App\Filament\Resources\Sites\SiteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\CreateRecord;

class CreateSite extends CreateRecord
{
    protected static string $resource = SiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            MultiSiteAction::make(),
            CopySshPubKeyAction::make(),
        ];
    }
}
