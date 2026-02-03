<?php

namespace App\Jobs;

use App\Enums\SiteStatus;
use App\Jobs\Traits\CanDelete;
use App\Models\Site;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteSite implements ShouldQueue
{
    use CanDelete, Queueable;

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

        $this->site->update(['status' => SiteStatus::DELETED]);

        $this->site->delete();
    }
}
