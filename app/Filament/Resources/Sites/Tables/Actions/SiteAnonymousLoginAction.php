<?php

namespace App\Filament\Resources\Sites\Tables\Actions;

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
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
            $email = Cache::get('global_admin_email', config('site.global_admin_email', 'admin@master.com'));
            $password = Cache::get('global_admin_password', config('site.global_admin_password', 'admin123'));
            $token = Str::random(32);
            $expires = now()->addSeconds(60)->timestamp;

            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) ? 'https://' : 'http://';
            $baseUrl = $scheme.rtrim($record->domain, '/').'/secure-anonymous-login/'.$token;

            $queryParams = [
                'email' => $email,
                'password' => $password,
                'expires' => $expires,
            ];

            ksort($queryParams);
            $queryString = http_build_query($queryParams);
            // Generate payload signature (independent of server APP_KEY differences)
            $signatureSecret = config('site.anonymous_login_secret', 'master_auto_login_secret');
            $signature = hash_hmac('sha256', $email.'|'.$password.'|'.$expires, $signatureSecret);

            return $baseUrl.'?'.$queryString.'&signature='.$signature;
        });
    }
}
