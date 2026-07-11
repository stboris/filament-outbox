<?php

namespace Stboris\FilamentOutbox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stboris\FilamentOutbox\Contracts\Endpoint;

class OutboxEndpoint extends Model implements Endpoint
{
    protected $guarded = [];

    protected $casts = [
        'secret' => 'encrypted',
        'settings' => 'array',
        'environments' => 'array',
        'enabled' => 'boolean',
    ];

    public static function findByName(string $name): ?self
    {
        return static::query()->where('name', $name)->first();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(OutboxMessage::class);
    }

    /**
     * An empty environments list means the endpoint is active everywhere.
     */
    public function matchesEnvironment(): bool
    {
        return empty($this->environments) || app()->environment($this->environments);
    }

    public function isActive(): bool
    {
        return $this->enabled && $this->matchesEnvironment();
    }

    public function setting(string $key): mixed
    {
        return data_get($this->settings, $key);
    }

    public function key(): int|string|null
    {
        return $this->getKey();
    }

    public function channel(): string
    {
        return $this->getAttribute('channel');
    }

    public function url(): string
    {
        return $this->getAttribute('url');
    }

    public function secret(): ?string
    {
        return $this->getAttribute('secret');
    }
}
