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
        $this->sourceFtp = $this->buildFtpStorage($this->site->parent);
        $this->targetFtp = $this->buildFtpStorage($this->site);
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

    private function buildFtpStorage(Site $site): Filesystem
    {
        return Storage::build([
            'driver' => 'ftp',
            'host' => $site->hosting->server->ip,
            'username' => $site->hosting->username,
            'password' => $site->hosting->password,

            // Optional but recommended
            'port' => $site->hosting->server->ftp_port,
            'root' => env('FTP_ROOT', '/'),
            'passive' => true,
            'ssl' => false,
            'timeout' => 30,
        ]);
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
