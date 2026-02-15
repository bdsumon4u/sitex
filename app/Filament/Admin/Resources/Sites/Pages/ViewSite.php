<?php

namespace App\Filament\Admin\Resources\Sites\Pages;

use App\Filament\Admin\Resources\Sites\SiteResource;
use App\Filament\Resources\Sites\Pages\Actions\MultiSiteAction;
use App\Filament\Resources\Sites\Tables\Actions\ForceUpdateAction;
use App\Filament\Resources\Sites\Tables\Actions\SiteUpdateAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSite extends ViewRecord
{
    protected static string $resource = SiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            MultiSiteAction::make(),
            Action::make('activities')
                ->label('View Activities')
                ->url($this->getResource()::getUrl('activities', ['record' => $this->record])),
            EditAction::make(),
            ActionGroup::make([
                SiteUpdateAction::make(),
                ForceUpdateAction::make(),
            ]),
        ];
    }
}
