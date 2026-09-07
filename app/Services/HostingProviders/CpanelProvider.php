<?php

declare(strict_types=1);

namespace App\Services\HostingProviders;

use App\Enums\SiteStatus;
use App\Models\Hosting;
use App\Models\Site;
use App\Services\HostingProviders\Contracts\HasEmailSupport;
use App\Services\HostingProviders\Contracts\HostingProvider;
use App\Services\HostingProviders\Contracts\NeedsSshAuthorization;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CpanelProvider implements HasEmailSupport, HostingProvider, NeedsSshAuthorization
{
    public function createNewDomain(Site $site): void
    {
        if ($site->directory === 'public_html') {
            return;
        }

        $data = $this->cpanelApiCall($site->hosting, 'AddonDomain', 'addaddondomain', [
            'dir' => $site->directory,
            'newdomain' => $site->domain,
            'subdomain' => Str::beforeLast($site->domain, '.'),
        ], 'cpanelresult');

        if (array_key_exists('error', $data) && ! str($data['error'])->contains('already exists')) {
            $site->update(['status' => SiteStatus::DEPLOY_FAILED]);
            throw new \Exception($data['error']);
        }
    }

    public function createOrUpdateEmailAccount(Site $site): void
    {
        $data = $this->cpanelApiCall($site->hosting, 'Email', 'addpop', [
            'domain' => $site->domain,
            'email' => $site->email_username,
            'password' => $site->email_password,
        ], 'cpanelresult');

        if (array_key_exists('error', $data)) {
            throw_unless(Str::endsWith($data['error'], 'already exists!'), $data['error']);

            $data = $this->cpanelApiCall($site->hosting, 'Email', 'passwdpop', [
                'domain' => $site->domain,
                'email' => $site->email_username,
                'password' => $site->email_password,
            ], 'cpanelresult');
        }

        if (array_key_exists('error', $data)) {
            $site->update(['status' => SiteStatus::DEPLOY_FAILED]);
            throw new \Exception($data['error']);
        }
    }

    public function createOrUpdateDatabaseAndUser(Site $site): void
    {
        $data = $this->cpanelApiCall($site->hosting, 'MysqlFE', 'createdb', [
            'db' => $site->prefixed_database_name,
        ], 'cpanelresult');

        if (array_key_exists('error', $data)) {
            throw_unless(Str::contains($data['error'], 'already exists.'), $data['error']);
        }

        $data = $this->cpanelApiCall($site->hosting, 'MysqlFE', 'createdbuser', [
            'dbuser' => $site->prefixed_database_user,
            'password' => $site->database_pass,
        ], 'cpanelresult');

        if (array_key_exists('error', $data)) {
            throw_unless(Str::contains($data['error'], 'already exists.'), $data['error']);

            $data = $this->cpanelApiCall($site->hosting, 'MysqlFE', 'changedbuserpassword', [
                'dbuser' => $site->prefixed_database_user,
                'password' => $site->database_pass,
            ], 'cpanelresult');

            if (array_key_exists('error', $data)) {
                $site->update(['status' => SiteStatus::DEPLOY_FAILED]);
                throw new \Exception($data['error']);
            }
        }

        $data = $this->cpanelApiCall($site->hosting, 'MysqlFE', 'setdbuserprivileges', [
            'db' => $site->prefixed_database_name,
            'dbuser' => $site->prefixed_database_user,
            'privileges' => 'ALL PRIVILEGES',
        ], 'cpanelresult');

        if (array_key_exists('error', $data)) {
            $site->update(['status' => SiteStatus::DEPLOY_FAILED]);
            throw new \Exception('Failed to set privileges on database');
        }
    }

    public function deleteDomain(Site $site): void
    {
        $subdomain = Str::of($site->domain)
            ->beforeLast('.')
            ->append('.')
            ->append($site->hosting->domain);

        $data = $this->cpanelApiCall($site->hosting, 'AddonDomain', 'deladdondomain', [
            'domain' => $site->domain,
            'subdomain' => $subdomain,
        ], 'cpanelresult');

        if (array_key_exists('error', $data) && ! Str::contains($data['error'], 'does not correspond to')) {
            throw new \Exception('Failed to delete site from hosting server: '.$data['error']);
        }
    }

    public function authorizeSshKey(Hosting $hosting): void
    {
        try {
            $keyName = Hosting::sshKeyName();
            $privateKeyPath = Hosting::sshPrivateKeyPath();
            $publicKey = Hosting::sshPublicKey();

            if (! $privateKeyPath || ! $publicKey || ! file_exists($privateKeyPath)) {
                Log::warning('SSH private or public key for '.$keyName.' was not found, skipping cPanel SSH key authorization.');

                return;
            }

            $key = (string) file_get_contents($privateKeyPath);
            if (trim($key) === '' || trim($publicKey) === '') {
                Log::warning('SSH key content is empty, skipping cPanel SSH key authorization.');

                return;
            }

            $ftp = $hosting->ftp();
            $ftp->put('.ssh/'.$keyName, $key, 'private');
            $ftp->put('.ssh/'.$keyName.'.pub', $publicKey, 'private');

            Log::info('Importing SSH key for '.$hosting->domain);
            $importResponse = $this->cpanelApiCall($hosting, 'SSH', 'importkey', [
                'name' => $keyName,
                'key' => $publicKey,
                'type' => 'public',
            ], 'cpanelresult');

            if (array_key_exists('error', $importResponse)) {
                Log::error('Failed to import SSH key: '.$importResponse['error']);
            }

            Log::info('Authorizing SSH key for '.$hosting->domain);
            $authorizeResponse = $this->cpanelApiCall($hosting, 'SSH', 'authkey', [
                'key' => $keyName,
                'action' => 'authorize',
            ], 'cpanelresult');

            if (array_key_exists('error', $authorizeResponse)) {
                Log::error('Failed to authorize SSH key: '.$authorizeResponse['error']);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to authorize SSH key: '.$e->getMessage());
        }
    }

    public function toggleCronJob(Site $site, bool $enable): void
    {
        $cronCmd = "cd {$site->full_directory} && ./php artisan schedule:run >> /dev/null 2>&1";

        try {
            $cronList = $this->cpanelApiCall($site->hosting, 'Cron', 'listcron', [], 'cpanelresult');
            $data = $cronList['data'] ?? [];

            // Remove existing matching crons
            foreach ($data as $item) {
                $command = $item['command'] ?? '';
                if (str_contains($command, $site->full_directory) || str_contains($command, $site->domain)) {
                    if (isset($item['line'])) {
                        $this->cpanelApiCall($site->hosting, 'Cron', 'remove_line', [
                            'line' => (string) $item['line'],
                        ], 'cpanelresult');
                    }
                }
            }

            if ($enable) {
                $this->cpanelApiCall($site->hosting, 'Cron', 'add_line', [
                    'command' => $cronCmd,
                    'day' => '*',
                    'hour' => '*',
                    'minute' => '*',
                    'month' => '*',
                    'weekday' => '*',
                ], 'cpanelresult');
            }

            Log::info("cPanel Cron toggled successfully for site {$site->domain}", ['enable' => $enable]);
        } catch (\Throwable $e) {
            Log::warning("cPanel API Cron toggle failed: {$e->getMessage()}. Attempting user-level SSH crontab fallback.");
            $this->toggleCronViaUserSsh($site, $enable);
        }
    }

    private function toggleCronViaUserSsh(Site $site, bool $enable): void
    {
        $sitePath = rtrim($site->full_directory, '/');
        $enableFlag = $enable ? '1' : '0';

        $sshScript = <<<BASH
set -e
site_path="{$sitePath}"
enable="{$enableFlag}"

php_exec="./php"
if [ ! -x "\$php_exec" ]; then
    php_exec="\$(command -v php || echo 'php')"
fi

cron_cmd="* * * * * cd \$site_path && \$php_exec artisan schedule:run >> /dev/null 2>&1"

if command -v crontab >/dev/null 2>&1; then
    current_crontab="\$(crontab -l 2>/dev/null || true)"
    filtered="\$(echo "\$current_crontab" | grep -vF "{$site->domain}" | grep -vF "\$site_path" || true)"

    if [ "\$enable" = "1" ]; then
        new_crontab="\$(printf "%s\n%s" "\$filtered" "\$cron_cmd" | sed '/^$/d')"
    else
        new_crontab="\$(echo "\$filtered" | sed '/^$/d')"
    fi

    echo "\$new_crontab" | crontab - || true
fi
BASH;

        $keyPath = Hosting::sshPrivateKeyPath();
        if (! $keyPath || ! file_exists($keyPath)) {
            Log::error('SSH private key does not exist for cPanel crontab toggle.');

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
            Log::error('User-level SSH crontab toggle failed: '.$process->getErrorOutput());
        }
    }

    public function cpanelApiCall(Hosting $hosting, string $module, string $action, array $params = [], ?string $key = null): array
    {
        $endpoint = "https://{$hosting->connectionIp()}:2083/json-api/cpanel";

        $response = Http::withHeader('Authorization', "cpanel {$hosting->username}:{$hosting->token}")
            ->acceptJson()
            ->withoutVerifying()
            ->throw()
            ->get($endpoint, [
                'api.version' => 1,
                'cpanel_jsonapi_func' => $action,
                'cpanel_jsonapi_user' => $hosting->username,
                'cpanel_jsonapi_module' => $module,
                'cpanel_jsonapi_apiversion' => 2,
            ] + $params);

        return optional($response)->json($key, []);
    }
}
