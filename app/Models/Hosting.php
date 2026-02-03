<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class Hosting extends Model
{
    use BelongsToOrganization;

    protected $hidden = [
        // 'password',
        // 'token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'token' => 'encrypted',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function sites()
    {
        return $this->hasMany(Site::class);
    }

    public function cPanel(string $module, string $action, array $params = [], ?string $key = null): array
    {
        $endpoint = "https://{$this->server->ip}:2083/json-api/cpanel";

        /** @var \Illuminate\Http\Client\Response */
        $response = Http::withHeader('Authorization', "cpanel $this->username:$this->token")
            ->acceptJson()
            ->withoutVerifying()
            ->throw()
            ->get($endpoint, [
                'api.version' => 1,
                'cpanel_jsonapi_func' => $action,
                'cpanel_jsonapi_user' => $this->username,
                'cpanel_jsonapi_module' => $module,
                'cpanel_jsonapi_apiversion' => 2,
            ] + $params);

        return $response->json($key, []);
    }

    public function ftp(): Filesystem
    {
        return Storage::build([
            'driver' => 'ftp',
            'host' => $this->server->ip,
            'username' => $this->username,
            'password' => $this->password,
            // Optional but recommended
            'port' => $this->server->ftp_port,
            'root' => env('FTP_ROOT', '/'),
            'passive' => true,
            'ssl' => false,
            'timeout' => 30,
        ]);
    }

    public function copySshKey(): void
    {
        $ftp = $this->ftp();
        $key = Storage::disk('local')->get('HOTASH');
        $pubKey = Storage::disk('local')->get('HOTASH.pub');
        $ftp->put('.ssh/HOTASH', $key, 'private');
        $ftp->put('.ssh/HOTASH.pub', $pubKey, 'private');
    }
}
