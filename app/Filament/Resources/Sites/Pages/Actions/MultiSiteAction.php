<?php

namespace App\Filament\Resources\Sites\Pages\Actions;

use App\Filament\Resources\Sites\Pages\MultiSite;
use Filament\Actions\Action;
use Filament\Support\Colors\Color;

class MultiSiteAction extends Action
{
    public static function make(?string $name = 'multi-site'): static
    {
        return parent::make($name)
            ->label('Multi site')
            ->color(Color::Emerald)
            ->url(MultiSite::getUrl());
    }
}
