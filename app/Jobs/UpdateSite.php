<?php

namespace App\Jobs;

use App\Enums\SiteStatus;
use App\Models\Site;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Ssh\Ssh;

class UpdateSite implements ShouldQueue
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
        $this->site->update(['status' => SiteStatus::UPDATING]);

        try {
            $this->site->hosting->copySshKey();
            Log::info('Updating site '.$this->site->name.' on '.$this->site->domain);
            $process = Ssh::create($this->site->hosting->username, $this->site->hosting->connectionIp())
                ->usePrivateKey(Storage::disk('local')->path('HOTASH'))
                ->disablePasswordAuthentication()
                ->disableStrictHostKeyChecking()
                ->setTimeout(700)
                ->execute([
                    'cd '.$this->site->full_directory,
                    'git config --global --add safe.directory "$(pwd)" 2>/dev/null || true',
                    'remote_name=$(git remote 2>/dev/null | head -n 1 || echo "origin")',
                    'git fetch "$remote_name" --prune 2>/dev/null || true',
                    'if git rev-parse --verify "${remote_name}/main" >/dev/null 2>&1; then export SITE_UPDATER_TARGET_COMMIT="${remote_name}/main"; elif git rev-parse --verify "${remote_name}/master" >/dev/null 2>&1; then export SITE_UPDATER_TARGET_COMMIT="${remote_name}/master"; else export SITE_UPDATER_TARGET_COMMIT=$(git rev-parse HEAD 2>/dev/null || echo ""); fi',
                    'export SITE_UPDATER_FORCE_RESET=1',
                    'if [ -f ./server_deploy.sh ]; then chmod +x ./server_deploy.sh && ./server_deploy.sh; elif [ -f ./site-update.sh ]; then chmod +x ./site-update.sh && ./site-update.sh; else echo "No deploy script found"; exit 1; fi',
                ]);

            if (! $process->isSuccessful()) {
                $this->site->update(['status' => SiteStatus::UPDATE_FAILED]);
                $errorOutput = trim($process->getErrorOutput());
                $standardOutput = trim($process->getOutput());
                $exitCode = $process->getExitCode();

                throw new \RuntimeException(
                    'SSH command failed. Exit code: '.$exitCode
                    .' Error output: '.($errorOutput !== '' ? $errorOutput : '[none]')
                    .' Standard output: '.($standardOutput !== '' ? $standardOutput : '[none]')
                );
            }
            $this->site->update([
                'status' => SiteStatus::SITE_ACTIVE,
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            $this->site->update(['status' => SiteStatus::UPDATE_FAILED]);
            throw new \RuntimeException('Update failed during remote deploy execution. Error: '.$e->getMessage());
        }
    }
}
