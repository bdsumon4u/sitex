<?php

namespace App\Filament\Resources\Sites\Tables\Actions;

use App\Jobs\ForceUpdate;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;

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
        $this->modalHeading(fn (): string => __('Confirm Force Update'));
        $this->successNotificationTitle(__('Updating'));
        $this->requiresConfirmation();
        $this->modalContent(new HtmlString(
            '<strong>This will reset the repository and force update the site.</strong>'
        ));
        $this->color(Color::Yellow);
        $this->icon('heroicon-o-arrow-path');

        $this->action(function (Model $record, array $data) {
            if (! Hash::check($data['password'], Filament::auth()->user()->password)) {
                Notification::make()
                    ->title('Invalid password')
                    ->body('The password you entered is incorrect')
                    ->danger()
                    ->send();

                $this->failure();

                return new Halt;
            }

            ForceUpdate::dispatch($record)->onQueue('high');
        });

        $this->schema([
            TextInput::make('password')
                ->password()
                ->placeholder('Enter password to force update')
                ->required(),
        ]);
    }
}
