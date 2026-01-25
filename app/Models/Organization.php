<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\UlidBinaryCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Symfony\Component\Uid\Ulid;

final class Organization extends Model
{
    use Notifiable;

    protected $fillable = [
        'name', 'ulid', 'phone',
    ];

    public function resolveRouteBinding(mixed $value, mixed $field = null): Model
    {
        if ($field === 'ulid' && Str::isUlid($value)) {
            $value = Ulid::fromString($value)->toBinary();
        }

        $record = parent::resolveRouteBinding($value, $field);

        throw_unless($record, (new ModelNotFoundException)->setModel(self::class, [$value]));

        return $record;
    }

    /**
     * @return BelongsToMany<User, $this, \Illuminate\Database\Eloquent\Relations\Pivot>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    protected static function booted(): void
    {
        self::creating(function (Organization $organization): void {
            $organization->ulid = mb_strtolower((string) Str::ulid());
        });
    }

    protected function casts(): array
    {
        return [
            'ulid' => UlidBinaryCast::class,
        ];
    }
}
