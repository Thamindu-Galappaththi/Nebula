<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class UserTrackingData
{
    public static function forCreate(): array
    {
        $userId = Auth::id();

        return $userId === null ? [] : [
            'created_by' => $userId,
            'updated_by' => $userId,
        ];
    }

    public static function forUpdate(): array
    {
        $userId = Auth::id();

        return $userId === null ? [] : [
            'updated_by' => $userId,
        ];
    }

    public static function forCreateOn(string $table): array
    {
        return self::existingColumns($table, self::forCreate());
    }

    public static function forUpdateOn(string $table): array
    {
        return self::existingColumns($table, self::forUpdate());
    }

    private static function existingColumns(string $table, array $payload): array
    {
        if ($payload === [] || !Schema::hasTable($table)) {
            return [];
        }

        return array_filter(
            $payload,
            static fn ($column) => Schema::hasColumn($table, $column),
            ARRAY_FILTER_USE_KEY
        );
    }
}
