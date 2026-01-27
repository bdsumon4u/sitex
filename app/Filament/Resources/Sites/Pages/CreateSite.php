<?php

namespace App\Filament\Resources\Sites\Pages;

use App\Enums\SiteStatus;
use App\Filament\Resources\Sites\Pages\Actions\CopySshPubKeyAction;
use App\Filament\Resources\Sites\Pages\Actions\MultiSiteAction;
use App\Filament\Resources\Sites\SiteResource;
use App\Jobs\AuthorizeSshKey;
use App\Jobs\CopySiteFromParent;
use App\Jobs\CreateDatabaseAndUser;
use App\Jobs\CreateEmailAccount;
use App\Jobs\CreateNewDomain;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

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

    protected function afterCreate(): void
    {
        if (! $this->getRecord()->parent_id) {
            return;
        }

        $record = $this->getRecord()->load('hosting.server');

        $record->update(['status' => SiteStatus::DEPLOYING]);

        Bus::chain([
            new AuthorizeSshKey($record),
            new CreateNewDomain($record),
            new CreateEmailAccount($record),
            new CreateDatabaseAndUser($record),
            new CopySiteFromParent($record),
        ])->catch(function (Throwable $e) {
            // A job within the chain has failed...
            Log::error($e->getMessage());
        })->dispatch();
    }
}
