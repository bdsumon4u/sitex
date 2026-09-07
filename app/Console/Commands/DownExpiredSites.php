<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\SiteStatus;
use App\Jobs\RunRemoteArtisanMaintenance;
use App\Models\Site;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DownExpiredSites extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sites:down-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Put expired active sites into maintenance mode using remote artisan command';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = now()->startOfDay()->toDateString();

        $expiredSites = Site::query()
            ->whereNotNull('renew_date')
            ->whereDate('renew_date', '<', $today)
            ->where('laravel_maintenance_mode', false)
            ->where('status', SiteStatus::SITE_ACTIVE)
            ->get();

        if ($expiredSites->isEmpty()) {
            $this->info('No expired active sites found requiring maintenance mode.');

            return self::SUCCESS;
        }

        $this->info("Found {$expiredSites->count()} expired active site(s). Queueing maintenance mode...");

        foreach ($expiredSites as $site) {
            Log::info("Queueing remote maintenance down for expired site: {$site->domain} (Renew date: {$site->renew_date})", [
                'site_id' => $site->id,
                'renew_date' => $site->renew_date,
            ]);

            RunRemoteArtisanMaintenance::dispatch($site, 'down')->onQueue('high');

            $this->line("Queued maintenance down for: {$site->domain}");
        }

        $this->info('All expired site maintenance jobs queued successfully.');

        return self::SUCCESS;
    }
}
