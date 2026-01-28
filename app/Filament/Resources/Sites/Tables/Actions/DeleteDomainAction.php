<?php

namespace App\Filament\Resources\Sites\Tables\Actions;

use App\Enums\SiteStatus;
use App\Jobs\DeleteFiles;
use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Model;

class DeleteDomainAction extends DeleteAction
{
    public static function getDefaultName(): ?string
    {
        return 'delete-domain';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Delete Domain'));

        $this->modalHeading(fn (): string => __('Delete Domain'));

        $this->successNotificationTitle(__('Deleting'));

        $this->using(static function (Model $record): ?bool {
            DeleteFiles::dispatch($record->load('hosting.server'));

            return $record->update(['status' => SiteStatus::DELETING]);
        });
    }
}
