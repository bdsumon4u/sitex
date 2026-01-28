<?php

namespace App\Filament\Admin\Resources\Sites\Pages;

use App\Filament\Admin\Resources\Sites\SiteResource;
use App\Filament\Resources\Sites\Pages\ListSites as ListSitesPage;

class ListSites extends ListSitesPage
{
    protected static string $resource = SiteResource::class;
}
