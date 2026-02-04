<?php

namespace App\Filament\Resources\Sites\Tables\Actions;

use App\Jobs\ForceUpdate;
use Filament\Actions\Action;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Model;

class ForceUpdateAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'force-update';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Force Update'));
        $this->successNotificationTitle(__('Updating'));
        $this->color(Color::Yellow);
        $this->icon('heroicon-o-arrow-path');

        $this->action(static fn (Model $record) => ForceUpdate::dispatch($record)->onQueue('high'));
    }
}
