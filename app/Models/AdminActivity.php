<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

// One entry in the admin activity log (see App\Models\Concerns\LogsAdminActivity)
class AdminActivity extends Model
{
    use Prunable;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'subject_type',
        'subject_id',
        'subject_label',
        'changes',
        'ip_address',
    ];

    protected function casts(): array
    {
        return ['changes' => 'array'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Removed after a year by the daily `model:prune`
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subYear());
    }

    // Record an action by the logged-in admin (no-op for customers, console and seeders)
    public static function record(string $action, ?string $label = null, ?Model $subject = null, ?array $changes = null): void
    {
        $user = auth()->user();

        if (! ($user instanceof User) || (app()->runningInConsole() && ! app()->runningUnitTests())) {
            return;
        }

        static::create([
            'user_id'       => $user->id,
            'user_name'     => $user->name,
            'action'        => $action,
            'subject_type'  => $subject ? class_basename($subject) : null,
            'subject_id'    => $subject?->getKey(),
            'subject_label' => $label ? mb_substr($label, 0, 255) : null,
            'changes'       => $changes ?: null,
            'ip_address'    => request()->ip(),
        ]);
    }
}
