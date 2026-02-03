<?php

namespace App\Jobs;

use App\Enums\SiteStatus;
use App\Models\Site;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AuthorizeSshKey implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private Site $site,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->site->update(['status' => SiteStatus::DEPLOYING]);

        $this->site->parent->hosting->copySshKey();
        $this->site->hosting->copySshKey();

        $this->authorize($this->site->parent);
        $this->authorize($this->site);
    }

    private function authorize(Site $site): void
    {
        Log::info('Authorizing SSH key for '.$site->domain);
        $data = $site->hosting->cPanel('SSH', 'authkey', [
            'key' => 'HOTASH',
            'action' => 'authorize',
        ], 'cpanelresult');

        if (array_key_exists('error', $data)) {
            Log::error('Failed to authorize SSH key: '.$data['error']);
        }
    }
}
