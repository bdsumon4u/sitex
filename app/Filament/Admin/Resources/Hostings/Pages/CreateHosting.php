<?php

namespace App\Filament\Admin\Resources\Hostings\Pages;

use App\Filament\Admin\Resources\Hostings\HostingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHosting extends CreateRecord
{
    protected static string $resource = HostingResource::class;
}
