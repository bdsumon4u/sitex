<?php

namespace App\Filament\Resources\Sites\Tables\Actions;

use App\Actions\DeploySite;
use Filament\Actions\Action;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class SiteRedeployAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'redeploy-site';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Redeploy'));
        $this->modalHeading(fn (): string => __('Confirm Redeploy'));
        $this->successNotificationTitle(__('Redeploying'));
        $this->requiresConfirmation();
        $this->modalContent(new HtmlString(
            '<strong>This will delete existing site and copy from parent site again.</strong>'
        ));
        $this->color(Color::Red);
        $this->icon('heroicon-o-arrow-path');

        $this->action(static fn (Model $record) => (new DeploySite)->handle($record));
    }
}
