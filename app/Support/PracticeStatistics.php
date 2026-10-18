<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\Feedback;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Live numbers about the practice for the admin dashboard.
 *
 * There is no work schedule or check-in yet, so "at work" and "at the practice"
 * are derived from today's appointments.
 */
class PracticeStatistics
{
    private CarbonInterface $now;

    public function __construct(?CarbonInterface $now = null)
    {
        $this->now = $now ?? now();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $today = Appointment::query()
            ->whereDate('date', $this->now->toDateString())
            ->with(['customer', 'dentist', 'assistant', 'room'])
            ->orderBy('starttime')
            ->get();

        $current = $today->filter(fn (Appointment $appointment) => $this->isRunning($appointment));

        return [
            'satisfaction' => $this->satisfaction(),
            'rooms' => $this->rooms($current),
            'customers' => [
                'presentNow' => $current->pluck('customer_id')->unique()->count(),
                'expectedLater' => $today->filter(fn ($appointment) => $appointment->starttime > $this->time())
                    ->pluck('customer_id')->unique()->count(),
                'today' => $today->pluck('customer_id')->unique()->count(),
            ],
            'employees' => $this->employees($today, $current),
            'dentistFeedback' => $this->dentistFeedback(),
            'latestFeedback' => Feedback::query()
                ->with(['customer:id,firstname,lastname', 'dentist:id,firstname,lastname'])
                ->latest()
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * @return array{average: float|null, count: int, positivePercentage: int|null}
     */
    private function satisfaction(): array
    {
        $count = Feedback::query()->count();

        if ($count === 0) {
            return ['average' => null, 'count' => 0, 'positivePercentage' => null];
        }

        return [
            'average' => round((float) Feedback::query()->avg('rating'), 1),
            'count' => $count,
            // 4 or 5 stars counts as a satisfied customer
            'positivePercentage' => (int) round(Feedback::query()->where('rating', '>=', 4)->count() / $count * 100),
        ];
    }

    /**
     * @param  Collection<int, Appointment>  $current
     * @return array<string, mixed>
     */
    private function rooms(Collection $current): array
    {
        $rooms = Room::query()->orderBy('name')->get()->map(function (Room $room) use ($current) {
            $appointment = $current->firstWhere('room_id', $room->room_id);

            return [
                'room_id' => $room->room_id,
                'name' => $room->name,
                'appointment' => $appointment ? [
                    'customer' => $appointment->customer?->name,
                    'dentist' => $appointment->dentist?->name,
                    'endtime' => $appointment->endtime,
                ] : null,
            ];
        });

        return [
            'total' => $rooms->count(),
            'inUse' => $rooms->whereNotNull('appointment')->count(),
            'list' => $rooms->values(),
        ];
    }

    /**
     * @param  Collection<int, Appointment>  $today
     * @param  Collection<int, Appointment>  $current
     * @return array<string, mixed>
     */
    private function employees(Collection $today, Collection $current): array
    {
        $staffIds = fn (Collection $appointments) => $appointments
            ->flatMap(fn (Appointment $appointment) => [$appointment->dentist_id, $appointment->assistant_id])
            ->filter()
            ->unique();

        $atWorkIds = $staffIds($today);
        $busyIds = $staffIds($current);

        $atWork = User::query()
            ->whereIn('id', $atWorkIds)
            ->with('role')
            ->orderBy('lastname')
            ->get()
            ->map(fn (User $employee) => [
                'id' => $employee->id,
                'name' => $employee->name,
                'role' => $employee->role?->name,
                'busy' => $busyIds->contains($employee->id),
            ]);

        return [
            'atWork' => $atWork->count(),
            'busyNow' => $busyIds->count(),
            'total' => User::query()->where('role_id', '!=', Role::customerId())->where('status', 'active')->count(),
            'list' => $atWork->values(),
        ];
    }

    /**
     * Average rating per dentist, best rated first.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function dentistFeedback(): Collection
    {
        return User::query()
            ->where('role_id', Role::idFor(Role::DENTIST))
            ->withCount('feedback')
            ->withAvg('feedback', 'rating')
            ->get()
            ->map(fn (User $dentist) => [
                'id' => $dentist->id,
                'name' => $dentist->name,
                'count' => $dentist->feedback_count,
                'average' => $dentist->feedback_avg_rating !== null ? round((float) $dentist->feedback_avg_rating, 1) : null,
            ])
            ->sortByDesc(fn ($dentist) => [$dentist['average'] ?? -1, $dentist['count']])
            ->values();
    }

    private function isRunning(Appointment $appointment): bool
    {
        return $appointment->starttime <= $this->time() && $appointment->endtime > $this->time();
    }

    private function time(): string
    {
        return $this->now->format('H:i:s');
    }
}
