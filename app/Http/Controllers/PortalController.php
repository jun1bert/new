<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $type = in_array($user->user_type, ['member', 'staff', 'administrator'], true)
            ? $user->user_type
            : 'member';

        $appointments = Appointment::query()->with(['client', 'service']);

        if ($type === 'member') {
            $appointments->where('user_id', $user->id);
        }

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $monthAppointments = (clone $appointments)
            ->whereBetween('scheduled_at', [$monthStart, $monthEnd])
            ->orderBy('scheduled_at')
            ->get();

        $previousAppointments = $type === 'member'
            ? (clone $appointments)->orderByDesc('scheduled_at')->limit(5)->get()
            : collect();
        $bookableServices = $type === 'member'
            ? Service::query()->where('is_active', true)->orderBy('category')->orderBy('name')->get()
            : collect();

        $calendarStart = $monthStart->copy()->startOfWeek(Carbon::SUNDAY);
        $calendarEnd = $monthEnd->copy()->endOfWeek(Carbon::SATURDAY);
        $calendarDays = collect();
        for ($day = $calendarStart->copy(); $day->lte($calendarEnd); $day->addDay()) {
            $calendarDays->push($day->copy());
        }

        $stats = [
            'total' => (clone $appointments)->count(),
            'pending' => (clone $appointments)->where('status', 'scheduled')->count(),
            'confirmed' => 0,
            'completed' => (clone $appointments)->where('status', 'completed')->count(),
        ];

        return view('dashboard', compact('user', 'type', 'stats', 'monthStart', 'monthAppointments', 'previousAppointments', 'bookableServices', 'calendarDays'));
    }
}
