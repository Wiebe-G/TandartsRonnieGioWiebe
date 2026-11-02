<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes every create, change and delete of the important models to the activity log.
 */
class ActivityObserver
{
    /** Changes to these fields are not worth a line (or must never be shown). */
    private const IGNORED = ['updated_at', 'created_at', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'email_verified_at'];

    /** Shown as "changed", without the old and new value. */
    private const HIDDEN = ['password'];

    public function created(Model $model): void
    {
        ActivityLog::record('created', $this->label($model).' aangemaakt', $this->type($model), $model->getKey());
    }

    public function updated(Model $model): void
    {
        $changes = [];

        foreach (array_keys($model->getChanges()) as $field) {
            if (in_array($field, self::IGNORED, true)) {
                continue;
            }

            $changes[$field] = in_array($field, self::HIDDEN, true)
                ? ['***', '***']
                : [$this->plain($model->getOriginal($field)), $this->plain($model->getAttribute($field))];
        }

        // e.g. only a new remember token after logging in
        if ($changes === []) {
            return;
        }

        ActivityLog::record('updated', $this->label($model).' gewijzigd', $this->type($model), $model->getKey(), $changes);
    }

    public function deleted(Model $model): void
    {
        ActivityLog::record('deleted', $this->label($model).' verwijderd', $this->type($model), $model->getKey());
    }

    private function type(Model $model): string
    {
        return match (true) {
            $model instanceof Appointment => 'Afspraak',
            $model instanceof Invoice => 'Factuur',
            $model instanceof Treatment => 'Behandeling',
            $model instanceof User => (int) $model->role_id === Role::customerId() ? 'Klant' : 'Medewerker',
            default => class_basename($model),
        };
    }

    /**
     * A readable name for the log line, e.g. "Klant Jan Jansen" or "Afspraak #12 (21-10-2026 09:00)".
     */
    private function label(Model $model): string
    {
        return $this->type($model).' '.match (true) {
            $model instanceof Appointment => "#{$model->appointment_id} (".$model->date?->format('d-m-Y').' '.substr((string) $model->starttime, 0, 5).')',
            $model instanceof Invoice => $model->number(),
            $model instanceof Treatment => $model->name,
            $model instanceof User => $model->name,
            default => '#'.$model->getKey(),
        };
    }

    private function plain(mixed $value): mixed
    {
        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i') : $value;
    }
}
