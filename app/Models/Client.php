<?php

namespace App\Models;

use App\Actions\DeleteClient;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SoftDeletes added 2026-05-09. Cascading is handled by App\Actions\DeleteClient via the
 * `deleting` listener, so any code path that calls $client->delete() gets the cascade for free.
 */
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'contact_name',
        'address',
        'contact_email',
        'phone',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<KitItem, $this> */
    public function kitItems(): HasMany
    {
        return $this->hasMany(KitItem::class);
    }

    /** @return HasMany<KitGroup, $this> */
    public function kitGroups(): HasMany
    {
        return $this->hasMany(KitGroup::class);
    }

    /** @return HasMany<Invoice, $this> */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Client $client): void {
            if ($client->isForceDeleting()) {
                return;
            }

            app(DeleteClient::class)->cascade($client);
        });
    }
}
