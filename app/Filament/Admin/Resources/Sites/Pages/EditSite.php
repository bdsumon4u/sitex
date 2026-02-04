<?php

namespace App\Filament\Admin\Resources\Sites\Pages;

use App\Filament\Admin\Resources\Sites\SiteResource;
use App\Filament\Resources\Sites\Tables\Actions\SiteDeleteAction;
use App\Filament\Widgets\SiteNavigator;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSite extends EditRecord
{
    protected static string $resource = SiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            SiteDeleteAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SiteNavigator::make(['currentSiteId' => $this->record->id]),
        ];
    }
}
