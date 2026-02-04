<?php

namespace App\Filament\Widgets;

use App\Filament\Admin\Resources\Sites\SiteResource;
use App\Models\Site;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;

class SiteNavigator extends Widget implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.widgets.site-navigator';

    protected int|string|array $columnSpan = 'full';

    public ?int $selectedSiteId = null;

    public function mount(?int $currentSiteId = null): void
    {
        $this->selectedSiteId = $currentSiteId;
    }

    public function updatedSelectedSiteId(): void
    {
        if ($this->selectedSiteId) {
            $this->redirect(SiteResource::getUrl('edit', ['record' => $this->selectedSiteId]), true);
        }
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('selectedSiteId')
                ->hiddenLabel()
                ->options(Site::query()->orderBy('domain')->pluck('domain', 'id'))
                ->searchable()
                ->live()
                ->placeholder('Type to search and select a site...')
                ->preload(),
        ];
    }
}
