<?php

namespace App\Filament\Resources\Sites\Pages;

use App\Filament\Resources\Sites\Pages\Actions\DeleteSitesAction;
use App\Filament\Resources\Sites\Pages\Actions\MultiSiteAction;
use App\Filament\Resources\Sites\SiteResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Cache;

class ListSites extends ListRecords
{
    protected static string $resource = SiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('global_admin')
                ->label('Global Admin')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->form([
                    TextInput::make('global_admin_email')
                        ->label('Global Admin Email')
                        ->default(fn () => Cache::get('global_admin_email', config('site.global_admin_email', 'admin@master.com')))
                        ->required(),
                    TextInput::make('global_admin_password')
                        ->label('Global Admin Password')
                        ->password()
                        ->revealable()
                        ->default(fn () => Cache::get('global_admin_password', config('site.global_admin_password', 'admin123')))
                        ->required(),
                ])
                ->action(function (array $data) {
                    Cache::forever('global_admin_email', $data['global_admin_email']);
                    Cache::forever('global_admin_password', $data['global_admin_password']);

                    Notification::make()
                        ->title('Global Admin Credentials Saved')
                        ->body('Login links will now use: '.$data['global_admin_email'])
                        ->success()
                        ->send();
                }),
            MultiSiteAction::make(),
            DeleteSitesAction::make(),
        ];
    }
}
