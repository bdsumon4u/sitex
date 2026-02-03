<?php

namespace App\Jobs;

use App\Enums\SiteStatus;
use App\Models\Site;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateDatabaseAndUser implements ShouldQueue
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
        Log::info('Creating database and user for '.$this->site->domain);
        $data = $this->site->hosting->cPanel('MysqlFE', 'createdb', [
            'db' => $this->site->prefixed_database_name,
        ], 'cpanelresult');

        if (array_key_exists('error', $data)) {
            throw_unless(Str::contains($data['error'], 'already exists.'), $data['error']);
        }

        $data = $this->site->hosting->cPanel('MysqlFE', 'createdbuser', [
            'dbuser' => $this->site->prefixed_database_user,
            'password' => $this->site->database_pass,
        ], 'cpanelresult');

        if (array_key_exists('error', $data)) {
            throw_unless(Str::contains($data['error'], 'already exists.'), $data['error']);

            Log::info('Database user '.$this->site->prefixed_database_user.' already exists. Updating password.');
            $data = $this->site->hosting->cPanel('MysqlFE', 'changedbuserpassword', [
                'dbuser' => $this->site->prefixed_database_user,
                'password' => $this->site->database_pass,
            ], 'cpanelresult');

            if (array_key_exists('error', $data)) {
                $this->site->update(['status' => SiteStatus::DEPLOY_FAILED]);
                throw new \Exception($data['error']);
            }
        }

        $data = $this->site->hosting->cPanel('MysqlFE', 'setdbuserprivileges', [
            'db' => $this->site->prefixed_database_name,
            'dbuser' => $this->site->prefixed_database_user,
            'privileges' => 'ALL PRIVILEGES',
        ], 'cpanelresult');

        if (array_key_exists('error', $data)) {
            $this->site->update(['status' => SiteStatus::DEPLOY_FAILED]);
            throw new \Exception('Failed to set privileges on database');
        }
    }
}
