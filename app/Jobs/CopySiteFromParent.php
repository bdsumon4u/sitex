<?php

namespace App\Jobs;

use App\Enums\SiteStatus;
use App\Models\Site;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Spatie\Ssh\Ssh;

class CopySiteFromParent implements ShouldQueue
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
        Log::info('Deploying site '.$this->site->name.' to '.$this->site->domain);
        $process = Ssh::create($this->site->hosting->username, $this->site->hosting->server->ip)
            ->usePrivateKey(storage_path('app/ssh/HOTASH'))
            ->disablePasswordAuthentication()
            ->disableStrictHostKeyChecking()
            ->enableQuietMode()
            ->setTimeout(1000)
            ->execute([
                'cd '.$this->site->parent->directory,
                './copy.sh '.collect([
                    '-s' => $this->site->name,
                    '-d' => $this->site->domain,
                    '-h' => $this->site->hosting->server->ip,
                    '-u' => $this->site->hosting->username,
                    '-db' => $this->site->hosting->database_name,
                    '-dbu' => $this->site->hosting->database_user,
                    '-dbp' => $this->site->hosting->database_pass,
                    '-mu' => $this->site->hosting->email_username,
                    '-mp' => $this->site->hosting->email_password,
                    '-dr' => $this->site->hosting->directory,
                ])
                    ->flatMap(fn ($val, $key) => [$key, '"'.$val.'"'])
                    ->implode(' '),
            ]);

        if (! $process->isSuccessful()) {
            $this->site->update(['status' => SiteStatus::DEPLOY_FAILED]);
            throw new \Exception($process->getErrorOutput());
        }

        $this->site->update(['status' => SiteStatus::SITE_ACTIVE]);
        Log::info('Site '.$this->site->name.' deployed successfully to '.$this->site->domain);
    }
}
