<?php

namespace App\Jobs;

use App\Models\Site;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AuthorizeSshKey implements ShouldQueue
{
    use Queueable;

    private Filesystem $sourceFtp;

    private Filesystem $targetFtp;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private Site $site,
    ) {
        $this->sourceFtp = $this->site->parent->hosting->ftp();
        $this->targetFtp = $this->site->hosting->ftp();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->copySshKeyViaFtp($this->sourceFtp);
        $this->copySshKeyViaFtp($this->targetFtp);

        $this->authorize($this->site->parent);
        $this->authorize($this->site);
    }

    private function copySshKeyViaFtp(Filesystem $ftp): void
    {
        $ftp->put('.ssh/HOTASH', Storage::disk('local')->get('HOTASH'), 'private');
        $ftp->put('.ssh/HOTASH.pub', Storage::disk('local')->get('HOTASH.pub'), 'private');
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
