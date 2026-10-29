<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ConductedByOption extends Model
{
    protected $table = 'conducted_by_options';

    protected $fillable = [
        'name',
    ];

    public static function defaults(): array
    {
        return [
            'SLT-MOBITEL Nebula Institute of Technology',
            'Pearson',
            'University of Hertfordshire',
        ];
    }

    public static function names(): array
    {
        $defaults = self::defaults();
        $saved = [];

        if (Schema::hasTable('conducted_by_options')) {
            $saved = static::query()->orderBy('name')->pluck('name')->all();
        }

        $custom = collect($saved)
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->reject(fn ($name) => in_array($name, ['0', '1', 'Other'], true))
            ->reject(fn ($name) => collect($defaults)->contains(
                fn ($default) => strcasecmp($default, $name) === 0
            ))
            ->unique(fn ($name) => mb_strtolower($name))
            ->sortBy(fn ($name) => mb_strtolower($name), SORT_NATURAL)
            ->values()
            ->all();

        return array_values(array_merge($defaults, $custom));
    }

    public static function remember(?string $name): void
    {
        $name = trim((string) $name);
        if ($name === '' || in_array($name, ['0', '1', 'Other'], true)) {
            return;
        }

        if (!Schema::hasTable('conducted_by_options')) {
            return;
        }

        $exists = static::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->exists();

        if (!$exists) {
            static::create(['name' => $name]);
        }
    }
}
