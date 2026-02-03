<?php

namespace App\Filament\Resources\Sites\Pages;

use App\Actions\DeploySite;
use App\Filament\Resources\Sites\Pages\Actions\CopySshPubKeyAction;
use App\Filament\Resources\Sites\Pages\Actions\MultiSiteAction;
use App\Filament\Resources\Sites\SiteResource;
use App\Models\Hosting;
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['organization_id']) && ! empty($data['hosting_id'])) {
            $data['organization_id'] = Hosting::query()
                ->whereKey($data['hosting_id'])
                ->value('organization_id');
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! $this->getRecord()->parent_id) {
            return;
        }

        $record = $this->getRecord()->load('hosting.server');

        (new DeploySite)->handle($record);
    }
}
