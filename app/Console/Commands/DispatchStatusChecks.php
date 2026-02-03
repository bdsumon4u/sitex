<?php

namespace App\Console\Commands;

use App\Enums\SiteStatus;
use App\Jobs\CheckSiteStatus;
use App\Models\Site;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class DispatchStatusChecks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sites:status-checks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch jobs to check the status of all sites';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Site::query()
            ->whereIn('status', [SiteStatus::SITE_ACTIVE, SiteStatus::SITE_DOWN, SiteStatus::PENDING])
            ->chunk(50, function (Collection $sites) {
                Bus::batch($sites->map(fn ($site) => new CheckSiteStatus($site))->toArray())
                    ->then(fn (Batch $batch) => Log::info('Site status checks completed.'))
                    ->catch(fn (Batch $batch, \Throwable $e) => Log::error('Error in site status checks: '.$e->getMessage()))
                    ->onQueue('low')
                    ->dispatch();
            });

        $this->info('Dispatched site status check jobs.');
    }
}
