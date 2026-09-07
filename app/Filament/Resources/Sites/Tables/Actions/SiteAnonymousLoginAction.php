<?php

namespace App\Filament\Resources\Sites\Tables\Actions;

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SiteAnonymousLoginAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'site-anonymous-login';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Login'));
        $this->icon('heroicon-o-arrow-right-on-rectangle');
        $this->color('success');
        $this->openUrlInNewTab();

        $this->url(function (Model $record): string {
            $secret = (string) config('site.anonymous_login_secret', 'hotash_secret_access');
            $expires = now()->addSeconds(60)->timestamp;
            $nonce = Str::random(16);

            $payload = base64_encode(json_encode([
                'expires' => $expires,
                'nonce' => $nonce,
            ], JSON_THROW_ON_ERROR));

            $signature = hash_hmac('sha256', $payload, $secret);

            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) ? 'https://' : 'http://';
            $baseUrl = $scheme.rtrim((string) $record->domain, '/').'/hotash-access';

            return $baseUrl.'?'.http_build_query([
                'payload' => $payload,
                'signature' => $signature,
            ]);
        });
    }
}
