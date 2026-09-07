<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sites\Tables\Actions;

use App\Jobs\ToggleRemoteCronJob;
use App\Models\Site;
use Filament\Actions\Action;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Model;

class SiteCronDisableAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'cron-disable';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Cron OFF'));
        $this->successNotificationTitle(__('Cron disabled successfully'));
        $this->color(Color::Gray);
        $this->icon('heroicon-o-clock');

        $this->visible(fn (Model $record): bool => $record instanceof Site && (bool) $record->cron_enabled);

        $this->action(static function (Model $record): void {
            if (! $record instanceof Site) {
                return;
            }

            $record->update(['cron_enabled' => false]);
            ToggleRemoteCronJob::dispatch($record, false);
        });
    }
}
