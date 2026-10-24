<?php

namespace App\Models\Concerns;

use App\Models\AdminActivity;
use BackedEnum;

/**
 * Logs create/update/delete of this model by a logged-in admin into the activity log.
 * Secrets and noise (timestamps, tokens, passwords) are never stored; long texts are shortened.
 */
trait LogsAdminActivity
{
    private static array $neverLogged = [
        'created_at', 'updated_at', 'password', 'remember_token', 'public_token',
        'app_authentication_secret', 'app_authentication_recovery_codes', 'last_login_at',
    ];

    public static function bootLogsAdminActivity(): void
    {
        static::created(fn ($model) => AdminActivity::record('created', $model->activityLabel(), $model));

        static::updated(function ($model) {
            $changes = [];

            foreach (array_keys($model->getChanges()) as $field) {
                if (in_array($field, self::$neverLogged, true)) {
                    continue;
                }

                $changes[$field] = [
                    'old' => self::forLog($model->getOriginal($field)),
                    'new' => self::forLog($model->getAttribute($field)),
                ];
            }

            if ($changes) {
                AdminActivity::record('updated', $model->activityLabel(), $model, $changes);
            }
        });

        static::deleted(fn ($model) => AdminActivity::record('deleted', $model->activityLabel(), $model));
    }

    // e.g. "Product: LED Bulb 12W", "Order: UE-100004"
    public function activityLabel(): string
    {
        $name = $this->reference ?? $this->name ?? $this->title ?? $this->email ?? ('#' . $this->getKey());

        return class_basename($this) . ': ' . $name;
    }

    private static function forLog(mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }

        if (is_array($value)) {
            return mb_strimwidth(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 200, '…');
        }

        if (is_string($value)) {
            return mb_strimwidth(strip_tags($value), 0, 200, '…');
        }

        return $value;
    }
}
