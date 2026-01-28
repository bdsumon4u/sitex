<?php

namespace App\Jobs\Traits;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

trait CanDelete
{
    public function canDelete(): bool
    {
        if ($this->isPrimary()) {
            Log::alert('Refusing to delete primary site '.$this->site->domain);
            Notification::make()
                ->title('Refusing to delete primary site '.$this->site->domain)
                ->danger()
                ->sendToDatabase(
                    $this->site->organization()->users()->get()
                )
                ->send();

            return false;
        }

        return true;
    }

    private function isPrimary(): bool
    {
        if ($this->site->domain === $this->site->hosting->domain) {
            return true;
        }

        if ($this->site->directory === 'public_html') {
            return true;
        }

        return false;
    }
}
