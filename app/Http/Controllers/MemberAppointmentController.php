<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberAppointmentController extends Controller
{
    public function show(Request $request, Appointment $appointment): View
    {
        abort_unless($appointment->user_id === $request->user()->id, 403);

        return view('member.appointments.show', [
            'appointment' => $appointment->load(['service', 'staff', 'payment.items.product']),
        ]);
    }

    public function receipt(Request $request, Appointment $appointment): View
    {
        abort_unless($appointment->user_id === $request->user()->id && $appointment->payment, 404);

        return view('studio.payments.receipt', [
            'payment' => $appointment->payment->load(['appointment.client', 'appointment.service', 'recorder', 'items.product']),
        ]);
    }
}
