<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Service;
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

        $appointments = Appointment::query()->with(['client', 'service', 'services']);

        if ($type === 'member') {
            $appointments->where('user_id', $user->id);
        }

        $previousAppointments = $type === 'member'
            ? (clone $appointments)->orderByDesc('scheduled_at')->limit(5)->get()
            : collect();
        $bookableServices = $type === 'member'
            ? Service::query()->where('is_active', true)->orderBy('category')->orderBy('name')->get()
            : collect();

        $stats = [
            'total' => (clone $appointments)->count(),
            'pending' => (clone $appointments)->where('status', 'pending')->count(),
            'scheduled' => (clone $appointments)->where('status', 'scheduled')->count(),
            'completed' => (clone $appointments)->where('status', 'completed')->count(),
        ];

        $calendar = $type !== 'member' ? app(SchedulingController::class)->calendarData($request) : [];
        $activeAttendance = $type === 'staff' ? $user->attendances()->whereNull('clocked_out_at')->first() : null;
        return view('dashboard', array_merge(compact('user', 'type', 'stats', 'previousAppointments', 'bookableServices', 'activeAttendance'), $calendar));
    }
}
