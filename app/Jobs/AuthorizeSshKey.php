<?php

namespace App\Jobs;

use App\Models\Site;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AuthorizeSshKey implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private Site $site,
    ) {
        $storage = Storage::build([
            'driver' => 'ftp',
            'host' => $this->site->hosting->server->ip,
            'username' => $this->site->hosting->username,
            'password' => $this->site->hosting->password,

            // Optional but recommended
            'port' => $this->site->hosting->server->ftp_port,
            'root' => env('FTP_ROOT', '/'),
            'passive' => true,
            'ssl' => false,
            'timeout' => 30,
        ]);

        $storage->put('.ssh/HOTASH', Storage::disk('local')->get('HOTASH'), 'private');
        $storage->put('.ssh/HOTASH.pub', Storage::disk('local')->get('HOTASH.pub'), 'private');

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
