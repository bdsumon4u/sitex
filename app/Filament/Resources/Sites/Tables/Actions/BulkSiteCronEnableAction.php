<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sites\Tables\Actions;

use App\Jobs\ToggleRemoteCronJob;
use App\Models\Site;
use Filament\Actions\BulkAction;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Collection;

class BulkSiteCronEnableAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'bulk-cron-enable';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Enable Cron'));
        $this->successNotificationTitle(__('Cron enabled for selected sites'));
        $this->color(Color::Emerald);
        $this->icon('heroicon-o-clock');

        $this->action(static function (Collection $records): void {
            $records
                ->filter(static fn ($record): bool => $record instanceof Site)
                ->each(static function (Site $site): void {
                    $site->update(['cron_enabled' => true]);
                    ToggleRemoteCronJob::dispatch($site, true);
                });
        });
    }
}
