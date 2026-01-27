<?php

namespace App\Jobs;

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
    ) {
        Log::info('Authorizing SSH key for '.$this->site->domain);
        $data = $this->site->hosting->cPanel('SSH', 'authkey', [
            'key' => 'HOTASH',
            'action' => 'authorize',
        ], 'cpanelresult');

        if (array_key_exists('error', $data)) {
            Log::error('Failed to authorize SSH key: '.$data['error']);
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
    }
}
