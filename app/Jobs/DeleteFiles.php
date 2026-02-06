<?php

namespace App\Jobs;

use App\Enums\SiteStatus;
use App\Jobs\Traits\CanDelete;
use App\Models\Site;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeleteFiles implements ShouldQueue
{
    use CanDelete, Queueable;

    public int $timeout = 1800;

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
        if (! $this->canDelete()) {
            return;
        }

        try {
            Log::info('Deleting files from filesystem via FTP', [
                'domain' => $this->site->domain,
                'directory' => $this->site->directory,
            ]);

            $ftp = $this->site->hosting->ftp();

            if ($ftp->exists($this->site->directory)) {
                $ftp->deleteDirectory($this->site->directory);
            }

            Log::info('Successfully deleted files from filesystem via FTP', [
                'domain' => $this->site->domain,
                'directory' => $this->site->directory,
            ]);

            $this->site->update(['status' => SiteStatus::DELETED]);
        } catch (Throwable $e) {
            Log::warning('Error deleting files from filesystem via FTP', [
                'domain' => $this->site->domain,
                'directory' => $this->site->directory,
                'error' => $e->getMessage(),
            ]);

            $this->site->update(['status' => SiteStatus::DELETE_FAILED]);
        }
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [100, 500, 1000];
    }
}
