<?php

namespace App\Jobs;

use App\Enums\SiteStatus;
use App\Models\Site;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateEmailAccount implements ShouldQueue
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
        Log::info('Creating email account '.$this->site->email_username);
        $data = $this->site->hosting->cPanel('Email', 'addpop', [
            'domain' => $this->site->domain,
            'email' => $this->site->email_username,
            'password' => $this->site->email_password,
        ], 'cpanelresult');

        if (array_key_exists('error', $data)) {
            throw_unless(Str::endsWith($data['error'], 'already exists!'), $data['error']);

            Log::info('Email account '.$this->site->email_username.' already exists. Updating password.');
            $data = $this->site->hosting->cPanel('Email', 'passwdpop', [
                'domain' => $this->site->domain,
                'email' => $this->site->email_username,
                'password' => $this->site->email_password,
            ], 'cpanelresult');
        }

        if (array_key_exists('error', $data)) {
            $this->site->update(['status' => SiteStatus::DEPLOY_FAILED]);
            throw new \Exception($data['error']);
        }
    }
}
