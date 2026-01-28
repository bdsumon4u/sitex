<?php

namespace App\Models;

use App\Enums\SiteStatus;
use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Site extends Model
{
    use BelongsToOrganization;

    protected $hidden = [
        // 'email_password',
        // 'database_pass',
    ];

    protected function casts(): array
    {
        return [
            'email_password' => 'encrypted',
            'database_pass' => 'encrypted',
            'status' => SiteStatus::class,
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'parent_id');
    }

    public function hosting(): BelongsTo
    {
        return $this->belongsTo(Hosting::class);
    }

    public function getPrefixedDatabaseNameAttribute(): string
    {
        return $this->hosting->username.'_'.$this->database_name;
    }

    public function getPrefixedDatabaseUserAttribute(): string
    {
        return $this->hosting->username.'_'.$this->database_user;
    }
}
