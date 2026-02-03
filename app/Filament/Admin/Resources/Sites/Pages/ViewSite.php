<?php

namespace App\Filament\Admin\Resources\Sites\Pages;

use App\Filament\Admin\Resources\Sites\SiteResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSite extends ViewRecord
{
    protected static string $resource = SiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('activities')
                ->label('View Activities')
                ->url($this->getResource()::getUrl('activities', ['record' => $this->record])),
            EditAction::make(),
        ];
    }
}
