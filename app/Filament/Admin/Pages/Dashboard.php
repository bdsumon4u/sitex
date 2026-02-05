<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\Sites\SiteResource;
use App\Filament\Resources\Sites\Pages\Actions\MultiSiteAction;
use Filament\Actions\CreateAction;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modelLabel('Site')
                ->url(SiteResource::getUrl('create')),
            MultiSiteAction::make(),
        ];
    }
}
