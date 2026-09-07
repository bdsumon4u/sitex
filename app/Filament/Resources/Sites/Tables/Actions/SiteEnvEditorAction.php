<?php

declare(strict_types=1);

namespace App\Filament\Resources\Sites\Tables\Actions;

use App\Models\Hosting;
use App\Models\Site;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Spatie\Ssh\Ssh;
use Throwable;

class SiteEnvEditorAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'edit_env';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('.env'));
        $this->icon('heroicon-o-document-text');
        $this->color(Color::Indigo);
        $this->modalHeading(fn (?Model $record): string => $record ? "Edit .env — {$record->domain}" : 'Edit .env');
        $this->modalDescription(__('View and edit the .env configuration file on the remote server.'));
        $this->modalWidth(Width::FiveExtraLarge);
        $this->modalSubmitActionLabel(__('Save .env'));

        $this->fillForm(function (Model $record): array {
            if (! $record instanceof Site || ! $record->hosting) {
                return ['env_content' => ''];
            }

            try {
                $record->hosting->copySshKey();

                $keyPath = Hosting::sshPrivateKeyPath();
                if (! $keyPath || ! file_exists($keyPath)) {
                    Notification::make()
                        ->title(__('SSH Key Not Found'))
                        ->body(__('SSH private key was not found.'))
                        ->danger()
                        ->send();

                    return ['env_content' => ''];
                }

                $process = Ssh::create($record->hosting->username, $record->hosting->connectionIp())
                    ->usePrivateKey($keyPath)
                    ->disablePasswordAuthentication()
                    ->disableStrictHostKeyChecking()
                    ->usePort($record->hosting->sshPort())
                    ->setTimeout(30)
                    ->execute([
                        'cd '.escapeshellarg($record->full_directory),
                        'cat .env 2>/dev/null || true',
                    ]);

                return [
                    'env_content' => $process->getOutput(),
                ];
            } catch (Throwable $e) {
                Log::error('Failed to load remote .env: '.$e->getMessage(), [
                    'site_id' => $record->id,
                    'domain' => $record->domain,
                ]);

                Notification::make()
                    ->title(__('Failed to fetch .env'))
                    ->body($e->getMessage())
                    ->danger()
                    ->send();

                return ['env_content' => ''];
            }
        });

        $this->schema([
            Textarea::make('env_content')
                ->label(__('Environment Variables (.env)'))
                ->helperText(__('Changes will be saved directly to the remote server and config cache will be cleared.'))
                ->rows(20)
                ->extraInputAttributes([
                    'class' => 'font-mono text-xs sm:text-sm tracking-wide',
                    'spellcheck' => 'false',
                    'style' => 'white-space: pre; overflow-wrap: normal; overflow-x: auto;',
                ])
                ->required(),
        ]);

        $this->action(function (Model $record, array $data): void {
            if (! $record instanceof Site || ! $record->hosting) {
                return;
            }

            try {
                $record->hosting->copySshKey();

                $keyPath = Hosting::sshPrivateKeyPath();
                if (! $keyPath || ! file_exists($keyPath)) {
                    Notification::make()
                        ->title(__('SSH Key Not Found'))
                        ->body(__('SSH private key was not found.'))
                        ->danger()
                        ->send();

                    throw new Halt;
                }

                $base64Content = base64_encode((string) $data['env_content']);
                $directory = escapeshellarg($record->full_directory);

                $updateScript = <<<BASH
set -e
cd {$directory}
cp .env .env.backup-$(date +%s) 2>/dev/null || true
echo '{$base64Content}' | base64 -d > .env
chmod 600 .env
./php artisan config:clear 2>/dev/null || php artisan config:clear 2>/dev/null || true
BASH;

                $process = Ssh::create($record->hosting->username, $record->hosting->connectionIp())
                    ->usePrivateKey($keyPath)
                    ->disablePasswordAuthentication()
                    ->disableStrictHostKeyChecking()
                    ->usePort($record->hosting->sshPort())
                    ->setTimeout(60)
                    ->execute([$updateScript]);

                if (! $process->isSuccessful()) {
                    $error = trim($process->getErrorOutput()) ?: trim($process->getOutput());
                    Log::error('Failed to update remote .env: '.$error, [
                        'site_id' => $record->id,
                        'domain' => $record->domain,
                    ]);

                    Notification::make()
                        ->title(__('Failed to save .env'))
                        ->body($error ?: __('Remote SSH execution failed.'))
                        ->danger()
                        ->send();

                    throw new Halt;
                }

                Notification::make()
                    ->title(__('.env Updated Successfully'))
                    ->body(__('The remote .env file has been updated and config cache cleared.'))
                    ->success()
                    ->send();
            } catch (Halt $halt) {
                throw $halt;
            } catch (Throwable $e) {
                Log::error('Exception updating remote .env: '.$e->getMessage(), [
                    'site_id' => $record->id,
                    'domain' => $record->domain,
                ]);

                Notification::make()
                    ->title(__('Failed to save .env'))
                    ->body($e->getMessage())
                    ->danger()
                    ->send();

                throw new Halt;
            }
        });
    }
}
