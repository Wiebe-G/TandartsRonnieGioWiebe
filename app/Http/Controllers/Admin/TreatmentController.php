<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Treatment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The admin version of the treatment pages.
 */
class TreatmentController extends Controller
{
    public function index(): Response
    {
        $usage = DB::table('appointment_treatment')
            ->selectRaw('treatment_id, count(*) as total')
            ->groupBy('treatment_id')
            ->pluck('total', 'treatment_id');

        return Inertia::render('admin/treatments/index', [
            'treatments' => Treatment::query()
                ->orderByDesc('active')
                ->orderBy('name')
                ->get()
                ->map(fn (Treatment $treatment) => [
                    ...$this->present($treatment),
                    'appointments_count' => (int) ($usage[$treatment->treatment_id] ?? 0),
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/treatments/create');
    }

    public function store(Request $request): RedirectResponse
    {
        Treatment::create([...$this->validated($request), 'active' => true]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Behandeling aangemaakt.']);

        return to_route('admin.treatments.index');
    }

    public function edit(Treatment $treatment): Response
    {
        return Inertia::render('admin/treatments/edit', [
            'treatment' => $this->present($treatment),
            'appointmentsCount' => DB::table('appointment_treatment')->where('treatment_id', $treatment->treatment_id)->count(),
        ]);
    }

    public function update(Request $request, Treatment $treatment): RedirectResponse
    {
        $treatment->update([
            // Only admins may change the price; other staff keep the current one
            ...$this->validated($request, withPrice: $request->user()->isAdmin()),
            // Inactive treatments can no longer be picked for a new appointment
            'active' => $request->validate(['active' => ['required', 'boolean']])['active'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Behandeling bijgewerkt.']);

        return to_route('admin.treatments.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $withPrice = true): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            ...($withPrice ? ['price' => ['required', 'numeric', 'min:0', 'max:999999.99']] : []),
            'duration' => ['required', 'integer', 'min:5', 'max:480'],
        ]);
    }

    /**
     * The model has no casts, so the types are fixed here for the pages.
     *
     * @return array<string, mixed>
     */
    private function present(Treatment $treatment): array
    {
        return [
            'treatment_id' => (int) $treatment->treatment_id,
            'name' => $treatment->name,
            'description' => $treatment->description,
            'price' => number_format((float) $treatment->price, 2, '.', ''),
            'duration' => (int) $treatment->duration,
            'active' => (bool) $treatment->active,
        ];
    }
}
