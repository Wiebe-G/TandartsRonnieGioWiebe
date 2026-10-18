<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Support\PracticeStatistics;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * The admin's own dashboard: practice statistics and today's appointments.
     */
    public function __invoke(): Response
    {
        return Inertia::render('admin/dashboard', [
            'statistics' => (new PracticeStatistics)->toArray(),
            'today' => Appointment::query()
                ->whereDate('date', today())
                ->with(['customer:id,firstname,lastname', 'dentist:id,firstname,lastname', 'room', 'treatments:treatment_id,name'])
                ->orderBy('starttime')
                ->get(),
        ]);
    }
}
