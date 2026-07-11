<?php

namespace Stboris\FilamentOutbox\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutboxEndpoint extends Model
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
}
