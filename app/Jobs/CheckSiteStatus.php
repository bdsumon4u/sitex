<?php

namespace App\Jobs;

use App\Enums\SiteStatus;
use App\Models\Site;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckSiteStatus implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private Site $site,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $status = SiteStatus::SITE_DOWN;

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::timeout(10)->head('http://'.$this->site->domain);

            if ($response->ok()) {
                $status = SiteStatus::SITE_ACTIVE;
            }
        } catch (\Exception $e) {

        }

        // Log the site status
        Log::info("Site: {$this->site->url} - Status: {$status->value}");

        // Update the site status in the database
        if ($this->site->status != $status) {
            $this->site->update(['status' => $status]);
        }
    }
}
