<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\HostingProvider;
use App\Models\Site;
use App\Services\HostingProviders\CpanelProvider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Ssh\Ssh;
use Throwable;

class ToggleRemoteCronJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Site $site,
        public bool $enable,
    ) {}

    public function handle(): void
    {
        Log::info('Starting remote cron toggle job', [
            'site_id' => $this->site->id,
            'domain' => $this->site->domain,
            'enable' => $this->enable,
        ]);

        try {
            $site = Site::query()->with('hosting')->find($this->site->id);
            if ($site === null || $site->hosting === null) {
                Log::warning('Cannot toggle remote cron: Site or hosting not found.', [
                    'site_id' => $this->site->id,
                ]);

                return;
            }

            $site->update([
                'cron_enabled' => $this->enable,
            ]);

            if ($site->hosting->provider() === HostingProvider::Cpanel) {
                app(CpanelProvider::class)->toggleCronJob($site, $this->enable);

                return;
            }

            try {
                $site->hosting->copySshKey();
            } catch (Throwable $sshKeyException) {
                Log::warning('SSH key copy failed during cron toggle: '.$sshKeyException->getMessage());
            }

            $sitePath = rtrim($site->full_directory, '/');
            $siteUser = $site->username;
            $enableFlag = $this->enable ? '1' : '0';

            $sshScript = <<<BASH
set -e

site_path="{$sitePath}"
site_user="{$siteUser}"
enable="{$enableFlag}"

php_exec="./php"
if [ ! -x "\$php_exec" ]; then
    php_exec="\$(command -v php || echo 'php')"
fi

cron_cmd="* * * * * cd \$site_path && \$php_exec artisan schedule:run >> /dev/null 2>&1"

clpctl_bin=""
if command -v clpctl >/dev/null 2>&1; then
    clpctl_bin="clpctl"
elif [ -x "/usr/local/bin/clpctl" ]; then
    clpctl_bin="/usr/local/bin/clpctl"
elif [ -x "/usr/bin/clpctl" ]; then
    clpctl_bin="/usr/bin/clpctl"
fi

domain_name="\$(basename "\$site_path")"

if [ -n "\$clpctl_bin" ] && [ -n "\$domain_name" ] && [ -n "\$site_user" ]; then
    if [ "\$enable" = "1" ]; then
        "\$clpctl_bin" cron:add --domainName="\$domain_name" --user="\$site_user" --period="* * * * *" --command="cd \$site_path && \$php_exec artisan schedule:run >> /dev/null 2>&1" >/dev/null 2>&1 || true
    else
        cron_list="\$("\$clpctl_bin" cron:list --domainName="\$domain_name" --user="\$site_user" 2>/dev/null || true)"
        cron_id="\$(echo "\$cron_list" | grep -i "schedule:run" | awk '{print \$1}' | head -n 1 || true)"
        if [ -n "\$cron_id" ]; then
            "\$clpctl_bin" cron:delete --id="\$cron_id" >/dev/null 2>&1 || true
        fi
    fi
fi

if command -v crontab >/dev/null 2>&1; then
    # 1. Clean root crontab
    root_crontab="\$(crontab -l 2>/dev/null || true)"
    filtered_root="\$(echo "\$root_crontab" | grep -vF "\$domain_name" | grep -vF "\$site_path" || true)"

    if [ "\$enable" = "1" ]; then
        new_root="\$(printf "%s\n%s" "\$filtered_root" "\$cron_cmd" | sed '/^$/d')"
    else
        new_root="\$(echo "\$filtered_root" | sed '/^$/d')"
    fi

    echo "\$new_root" | crontab - || true

    # 2. Clean site_user crontab if different
    if [ -n "\$site_user" ] && [ "\$site_user" != "root" ]; then
        user_crontab="\$(crontab -u "\$site_user" -l 2>/dev/null || true)"
        filtered_user="\$(echo "\$user_crontab" | grep -vF "\$domain_name" | grep -vF "\$site_path" || true)"

        if [ "\$enable" = "1" ]; then
            new_user="\$(printf "%s\n%s" "\$filtered_user" "\$cron_cmd" | sed '/^$/d')"
        else
            new_user="\$(echo "\$filtered_user" | sed '/^$/d')"
        fi

        echo "\$new_user" | crontab -u "\$site_user" - || true
    fi
fi
BASH;

            $keyPath = Storage::disk('local')->path('HOTASH');
            if (! file_exists($keyPath)) {
                Log::error('SSH private key HOTASH does not exist at path: '.$keyPath);

                return;
            }

            $process = Ssh::create($site->hosting->username, $site->hosting->connectionIp())
                ->usePrivateKey($keyPath)
                ->disablePasswordAuthentication()
                ->disableStrictHostKeyChecking()
                ->usePort($site->hosting->sshPort())
                ->setTimeout(60)
                ->execute([$sshScript]);

            if (! $process->isSuccessful()) {
                $stderr = trim($process->getErrorOutput());
                $stdout = trim($process->getOutput());
                $message = $stderr !== '' ? $stderr : ($stdout !== '' ? $stdout : 'No output.');

                Log::error('Remote cron toggle SSH failed', [
                    'site_id' => $site->id,
                    'enable' => $this->enable,
                    'message' => $message,
                ]);
            } else {
                Log::info('Remote cron toggle completed successfully', [
                    'site_id' => $site->id,
                    'enable' => $this->enable,
                    'output' => trim($process->getOutput()),
                ]);
            }
        } catch (Throwable $e) {
            Log::error('Remote cron toggle encountered exception', [
                'site_id' => $this->site->id,
                'enable' => $this->enable,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
