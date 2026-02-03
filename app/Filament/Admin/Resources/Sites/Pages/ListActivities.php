<?php

namespace App\Filament\Admin\Resources\Sites\Pages;

use App\Filament\Admin\Resources\Sites\SiteResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities as ListActivityLog;

class ListActivities extends ListActivityLog
{
    protected static string $resource = SiteResource::class;
}
