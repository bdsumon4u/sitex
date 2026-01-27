<?php

namespace App\Jobs;

use App\Enums\SiteStatus;
use App\Models\Site;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateNewDomain implements ShouldQueue
{
    use Queueable;

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
        if ($this->site->directory === 'public_html') {
            return;
        }

        Log::info('Creating addon domain '.$this->site->domain);
        $data = $this->site->hosting->cPanel('AddonDomain', 'addaddondomain', [
            'dir' => $this->site->directory,
            'newdomain' => $this->site->domain,
            'subdomain' => Str::beforeLast($this->site->domain, '.'),
        ], 'cpanelresult');

        if (array_key_exists('error', $data) && ! str($data['error'])->contains('already exists.')) {
            $this->site->update(['status' => SiteStatus::DEPLOY_FAILED]);
            throw new \Exception($data['error']);
        }
    }
}
