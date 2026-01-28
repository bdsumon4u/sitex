<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    use BelongsToOrganization;

    protected $hidden = [
        // 'password',
        // 'token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'token' => 'encrypted',
        ];
    }

    public function hostings(): HasMany
    {
        return $this->hasMany(Hosting::class);
    }
}
