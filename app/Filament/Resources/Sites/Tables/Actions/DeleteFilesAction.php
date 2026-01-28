<?php

namespace App\Filament\Resources\Sites\Tables\Actions;

use App\Enums\SiteStatus;
use App\Jobs\DeleteDomain;
use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Model;

class DeleteFilesAction extends DeleteAction
{
    public static function getDefaultName(): ?string
    {
        return 'delete-files';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Delete Files'));

        $this->modalHeading(fn (): string => __('Delete Files'));

        $this->successNotificationTitle(__('Deleting'));

        $this->using(static function (Model $record): ?bool {
            DeleteDomain::dispatch($record->load('hosting.server'));

            return $record->update(['status' => SiteStatus::DELETING]);
        });
    }
}
