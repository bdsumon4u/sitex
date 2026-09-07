<?php

use App\Enums\HostingProvider;
use App\Enums\SiteStatus;
use App\Jobs\RunRemoteArtisanMaintenance;
use App\Models\Hosting;
use App\Models\Organization;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function createSiteWithRenewDate(array $siteAttributes = []): Site
{
    $suffix = fake()->unique()->numerify('#####');

    $organization = Organization::query()->create([
        'name' => 'Org '.$suffix,
        'phone' => '01712345678',
    ]);

    $hosting = Hosting::query()->create([
        'organization_id' => $organization->id,
        'provider' => HostingProvider::Cpanel,
        'ip' => '127.0.0.1',
        'domain' => 'host-'.$suffix.'.test',
        'username' => 'user',
        'password' => 'secret',
        'token' => 'token',
        'renew_date' => now()->addMonth()->toDateString(),
    ]);

    return Site::query()->create(array_merge([
        'organization_id' => $organization->id,
        'hosting_id' => $hosting->id,
        'name' => 'Test Site',
        'domain' => 'site-'.$suffix.'.test',
        'directory' => 'public_html',
        'email_username' => 'admin@site.test',
        'email_password' => 'secret',
        'database_name' => 'db',
        'database_user' => 'dbu',
        'database_pass' => 'dbp',
        'status' => SiteStatus::SITE_ACTIVE,
        'laravel_maintenance_mode' => false,
        'renew_date' => null,
    ], $siteAttributes));
}

it('dispatches maintenance down job for expired active sites', function () {
    Queue::fake();

    $expiredSite = createSiteWithRenewDate([
        'renew_date' => now()->subDay()->toDateString(),
        'laravel_maintenance_mode' => false,
        'status' => SiteStatus::SITE_ACTIVE,
    ]);

    $futureSite = createSiteWithRenewDate([
        'renew_date' => now()->addDays(5)->toDateString(),
        'laravel_maintenance_mode' => false,
        'status' => SiteStatus::SITE_ACTIVE,
    ]);

    $alreadyDownSite = createSiteWithRenewDate([
        'renew_date' => now()->subDays(2)->toDateString(),
        'laravel_maintenance_mode' => true,
        'status' => SiteStatus::SITE_ACTIVE,
    ]);

    $this->artisan('sites:down-expired')
        ->assertSuccessful();

    Queue::assertPushedOn('high', RunRemoteArtisanMaintenance::class, function ($job) use ($expiredSite) {
        return $job->site->id === $expiredSite->id && $job->mode === 'down';
    });

    Queue::assertNotPushed(RunRemoteArtisanMaintenance::class, function ($job) use ($futureSite) {
        return $job->site->id === $futureSite->id;
    });

    Queue::assertNotPushed(RunRemoteArtisanMaintenance::class, function ($job) use ($alreadyDownSite) {
        return $job->site->id === $alreadyDownSite->id;
    });
});
