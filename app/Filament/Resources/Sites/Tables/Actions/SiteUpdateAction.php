<?php

namespace App\Filament\Resources\Sites\Tables\Actions;

use App\Jobs\UpdateSite;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class SiteUpdateAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'update-site';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Update Site'));
        $this->successNotificationTitle(__('Updating'));
        $this->icon('heroicon-o-arrow-path');

        $this->action(static fn (Model $record) => UpdateSite::dispatch($record)->onQueue('high'));
    }
}
