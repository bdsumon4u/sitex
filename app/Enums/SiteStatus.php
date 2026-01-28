<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum SiteStatus: string implements HasColor, HasIcon, HasLabel
{
    case PENDING = 'pending';
    case DEPLOYING = 'deploying';
    case SITE_ACTIVE = 'active';
    case SITE_DOWN = 'outage';
    case DEPLOY_FAILED = 'deploy_failed';
    case UPDATING = 'updating';
    case UPDATE_FAILED = 'update_failed';
    case DELETING = 'deleting';
    case DELETED = 'deleted';
    case DELETE_FAILED = 'delete_failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::DEPLOYING => 'Deploying',
            self::SITE_ACTIVE => 'Active',
            self::SITE_DOWN => 'Down',
            self::DEPLOY_FAILED => 'Deploy Failed',
            self::UPDATING => 'Updating',
            self::UPDATE_FAILED => 'Update Failed',
            self::DELETING => 'Deleting',
            self::DELETE_FAILED => 'Delete Failed',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::DEPLOYING => 'heroicon-o-rocket-launch',
            self::SITE_ACTIVE => 'heroicon-o-check-circle',
            self::SITE_DOWN => 'heroicon-o-x-circle',
            self::DEPLOY_FAILED => 'heroicon-o-exclamation-circle',
            self::UPDATING => 'heroicon-o-arrow-path',
            self::UPDATE_FAILED => 'heroicon-o-exclamation-circle',
            self::DELETING => 'heroicon-o-trash',
            self::DELETE_FAILED => 'heroicon-o-exclamation-circle',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::DEPLOYING => 'primary',
            self::SITE_ACTIVE => 'success',
            self::SITE_DOWN => 'warning',
            self::DEPLOY_FAILED => 'danger',
            self::UPDATING => 'secondary',
            self::UPDATE_FAILED => 'danger',
            self::DELETING => 'warning',
            self::DELETE_FAILED => 'danger',
        };
    }
}
