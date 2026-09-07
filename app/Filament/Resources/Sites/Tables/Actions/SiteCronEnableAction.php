<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sites\Tables\Actions;

use App\Jobs\ToggleRemoteCronJob;
use App\Models\Site;
use Filament\Actions\Action;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Model;

class SiteCronEnableAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'cron-enable';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Cron ON'));
        $this->successNotificationTitle(__('Cron enabled successfully'));
        $this->color(Color::Emerald);
        $this->icon('heroicon-o-clock');

        $this->visible(fn (Model $record): bool => $record instanceof Site && ! $record->cron_enabled);

        $this->action(static function (Model $record): void {
            if (! $record instanceof Site) {
                return;
            }

            $record->update(['cron_enabled' => true]);
            ToggleRemoteCronJob::dispatch($record, true);
        });
    }
}
