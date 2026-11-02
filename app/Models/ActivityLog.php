<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One line in the activity log: who did what, and when.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string $description
 * @property array<string, array{0: mixed, 1: mixed}>|null $changes
 * @property Carbon $created_at
 * @property-read User|null $user
 */
#[Fillable(['user_id', 'action', 'subject_type', 'subject_id', 'description', 'changes'])]
class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Write a line, by the logged-in user (or the given one, e.g. at login).
     *
     * @param  array<string, array{0: mixed, 1: mixed}>|null  $changes
     */
    public static function record(string $action, string $description, ?string $subjectType = null, ?int $subjectId = null, ?array $changes = null, ?User $user = null): void
    {
        static::create([
            'user_id' => ($user ?? auth()->user())?->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'description' => $description,
            'changes' => $changes ?: null,
        ]);
    }
}
