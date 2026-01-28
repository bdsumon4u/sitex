<?php

namespace App\Filament\Resources\Sites\Tables\Actions;

use App\Enums\SiteStatus;
use App\Jobs\DeleteDomain;
use App\Jobs\DeleteFiles;
use App\Jobs\DeleteSite;
use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Bus;

class DeleteSiteAction extends DeleteAction
{
    public static function getDefaultName(): ?string
    {
        return 'delete-site';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Delete Site'));

        $this->modalHeading(fn (): string => __('Delete Site'));

        $this->successNotificationTitle(__('Deleting'));

        $this->using(static function (Model $record): ?bool {
            $record->load('hosting.server');
            Bus::chain([
                new DeleteDomain($record),
                new DeleteFiles($record),
                new DeleteSite($record),
            ])->dispatch();

            return $record->update(['status' => SiteStatus::DELETING]);
        });
    }
}
