<?php

use App\Enums\HostingProvider;
use App\Filament\Resources\Sites\Tables\Actions\SiteEnvEditorAction;
use App\Models\Hosting;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->organization = Organization::create([
        'name' => 'Acme Org',
        'phone' => '01711111111',
    ]);
    $this->user->organizations()->attach($this->organization);

    $this->hosting = Hosting::create([
        'organization_id' => $this->organization->id,
        'provider' => HostingProvider::CloudPanel,
        'ip' => '127.0.0.1',
        'domain' => 'host-env.test',
        'username' => 'siteuser',
        'password' => 'secret',
        'token' => 'token',
        'renew_date' => now()->addYear()->toDateString(),
    ]);

    $this->site = Site::create([
        'organization_id' => $this->organization->id,
        'hosting_id' => $this->hosting->id,
        'name' => 'Env Test Site',
        'domain' => 'site-env.test',
        'directory' => 'htdocs/site-env.test',
        'email_username' => 'admin@site-env.test',
        'email_password' => 'secret',
        'database_name' => 'db_env',
        'database_user' => 'dbu_env',
        'database_pass' => 'dbp_env',
    ]);
});

it('can instantiate the site env editor action', function () {
    $action = SiteEnvEditorAction::make();

    expect($action)->toBeInstanceOf(\Filament\Actions\Action::class)
        ->and($action->getName())->toBe('edit_env');

    expect($action->record($this->site)->getModalHeading())->toBe('Edit .env — site-env.test');
});

it('resolves ssh key paths and public keys gracefully', function () {
    expect(Hosting::sshKeyName())->toBe('HOTASH');

    $keyPath = Hosting::sshPrivateKeyPath();
    expect($keyPath)->toBeString();
    expect(file_exists($keyPath))->toBeTrue();

    $pubKey = Hosting::sshPublicKey();
    expect($pubKey)->toBeString();
    expect($pubKey)->toContain('ssh-rsa');
});
