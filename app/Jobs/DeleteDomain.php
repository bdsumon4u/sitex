<?php

namespace App\Jobs;

use App\Enums\SiteStatus;
use App\Jobs\Traits\CanDelete;
use App\Models\Site;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class DeleteDomain implements ShouldQueue
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

        try {
            Log::info('Starting deletion process', [
                'domain' => $this->site->domain,
                'hosting_id' => $this->site->hosting_id,
                'directory' => $this->site->directory,
            ]);

            // Delete addon domain from cPanel
            $subdomain = Str::of($this->site->domain)
                ->beforeLast('.')
                ->append('.')
                ->append($this->site->hosting->domain);

            $data = $this->site->hosting->cPanel('AddonDomain', 'deladdondomain', [
                'domain' => $this->site->domain,
                'subdomain' => $subdomain,
            ], 'cpanelresult');

            if (! array_key_exists('error', $data) || Str::contains($data['error'], 'does not correspond to')) {
                Log::info('Successfully deleted from hosting server', [
                    'domain' => $this->site->domain,
                    'response' => $data,
                ]);

                $this->site->update(['status' => SiteStatus::DELETED]);
            } else {
                Log::error('Failed to delete from hosting server', [
                    'domain' => $this->site->domain,
                    'response' => $data,
                ]);

                $this->site->update(['status' => SiteStatus::DELETE_FAILED]);

                throw new \Exception('Failed to delete site from hosting server: '.$data['error']);
            }
        } catch (ConnectionException $e) {
            Log::error('Connection error while deleting site', [
                'domain' => $this->site->domain,
                'error' => $e->getMessage(),
            ]);

            $this->site->update(['status' => SiteStatus::DELETE_FAILED]);

            throw $e;
        } catch (Throwable $e) {
            Log::error('Error deleting site', [
                'domain' => $this->site->domain,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->site->update(['status' => SiteStatus::DELETE_FAILED]);

            throw $e;
        }
    }
}
