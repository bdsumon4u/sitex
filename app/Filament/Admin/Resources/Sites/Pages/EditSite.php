<?php

namespace App\Filament\Admin\Resources\Sites\Pages;

use App\Filament\Admin\Resources\Sites\SiteResource;
use App\Filament\Admin\Widgets\SiteNavigator;
use App\Filament\Resources\Sites\Pages\Actions\MultiSiteAction;
use App\Filament\Resources\Sites\Tables\Actions\ForceUpdateAction;
use App\Filament\Resources\Sites\Tables\Actions\SiteDeleteAction;
use App\Filament\Resources\Sites\Tables\Actions\SiteRedeployAction;
use App\Filament\Resources\Sites\Tables\Actions\SiteUpdateAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSite extends EditRecord
{
    protected static string $resource = SiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            MultiSiteAction::make(),
            ViewAction::make(),
            SiteDeleteAction::make(),
            ActionGroup::make([
                SiteRedeployAction::make(),
                SiteUpdateAction::make(),
                ForceUpdateAction::make(),
            ]),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SiteNavigator::make(['currentSiteId' => $this->record->id]),
        ];
    }
}
