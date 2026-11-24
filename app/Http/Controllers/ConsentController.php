<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\ConsentForm;
use App\Models\Service;
use App\Rules\DrawnSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ConsentController extends Controller
{
    public function index()
    {
        return view('admin.consents.index', ['services' => Service::orderBy('category')->orderBy('name')->get()]);
    }

    public function update(Request $request, Service $service)
    {
        $data = $request->validate([
            'requires_consent' => ['required', 'boolean'],
            'consent_content' => ['nullable', 'required_if:requires_consent,1', 'string', 'max:50000'],
        ]);
        $service->update($data);

        return back()->with('status', 'Consent wording saved for '.$service->name.'.');
    }

    public function create(Request $request, Appointment $appointment)
    {
        abort_unless($appointment->user_id === $request->user()->id && $request->user()->user_type === 'member', 403);
        if ($consent = $appointment->consentForm) {
            return redirect()->route('consents.show', $consent);
        }
        $service = $appointment->consent_service;
        abort_unless(in_array($appointment->status, ['pending', 'scheduled'], true) && $service && filled($service->consent_content), 404);

        return view('member.consents.create', compact('appointment', 'service'));
    }

    public function store(Request $request, Appointment $appointment)
    {
        abort_unless($appointment->user_id === $request->user()->id && $request->user()->user_type === 'member', 403);
        $data = $request->validate([
            'signature' => ['required', 'string', 'max:200000', new DrawnSignature],
            'acknowledged' => ['accepted'],
            'content_hash' => ['required', 'string', 'size:64'],
        ]);
        $consent = $this->saveSignature($appointment, $data, $request->user()->id);

        return redirect()->route('consents.show', $consent)->with('status', 'Your signed consent has been saved.');
    }

    private function saveSignature(Appointment $appointment, array $data, int $clientId, ?int $staffId = null): ConsentForm
    {
        return DB::transaction(function () use ($appointment, $data, $clientId, $staffId) {
            $locked = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->user_id === $clientId, 403);
            if ($existing = $locked->consentForm) {
                return $existing;
            }
            $locked->setRelation('services', $locked->services()->lockForUpdate()->get());
            if ($locked->services->isEmpty()) {
                $locked->setRelation('service', Service::whereKey($locked->service_id)->lockForUpdate()->firstOrFail());
            }
            $service = $locked->consent_service;
            abort_unless(in_array($locked->status, ['pending', 'scheduled'], true) && $service && filled($service->consent_content), 409);
            if (! hash_equals(hash('sha256', $service->name."\n".$service->consent_content), $data['content_hash'])) {
                throw ValidationException::withMessages(['content_hash' => 'The form has changed. Please reload this page and read the updated wording before signing.']);
            }
            return ConsentForm::create([
                'user_id' => $clientId,
                'recorded_by' => $staffId,
                'service_id' => $service->id,
                'appointment_id' => $locked->id,
                'signed_name' => $locked->client->name,
                'signature_strokes' => array_map(fn ($stroke) => array_map(fn ($point) => [(float) $point[0], (float) $point[1]], $stroke), json_decode($data['signature'], true)),
                'signed_at' => now(),
                'service_name' => $service->name,
                'content_snapshot' => $service->consent_content,
                'content_hash' => $data['content_hash'],
            ]);
        });
    }

    public function startTablet(Request $request, Appointment $appointment)
    {
        $service = $appointment->consent_service;
        abort_unless(in_array($appointment->status, ['pending', 'scheduled'], true)
            && $service && filled($service->consent_content) && ! $appointment->consentForm, 409);
        $request->session()->put('consent_tablet', [
            'appointment_id' => $appointment->id,
            'client_id' => $appointment->user_id,
            'staff_id' => $request->user()->id,
        ]);

        return redirect()->route('studio.consents.tablet');
    }

    public function tablet(Request $request)
    {
        $context = $request->session()->get('consent_tablet');
        if (! $context) {
            return redirect()->route('studio.appointments.index');
        }
        $appointment = Appointment::with(['client', 'service', 'services', 'consentForm'])->find($context['appointment_id']);
        // The locked screen must always offer staff a way to return, even if the booking changes.
        $available = $appointment && $appointment->user_id === $context['client_id']
            && in_array($appointment->status, ['pending', 'scheduled'], true)
            && $appointment->consent_service && filled($appointment->consent_service->consent_content);

        return view('studio.consents.tablet', compact('appointment', 'available'));
    }

    public function signTablet(Request $request)
    {
        $context = $request->session()->get('consent_tablet');
        abort_unless($context, 403);
        $data = $request->validate([
            'signature' => ['required', 'string', 'max:200000', new DrawnSignature],
            'acknowledged' => ['accepted'],
            'content_hash' => ['required', 'string', 'size:64'],
        ]);
        $appointment = Appointment::findOrFail($context['appointment_id']);
        $this->saveSignature($appointment, $data, $context['client_id'], $request->user()->id);

        return redirect()->route('studio.consents.tablet');
    }

    public function unlockTablet(Request $request)
    {
        $data = $request->validateWithBag('tabletUnlock', ['password' => ['required', 'string']]);
        if (! Hash::check($data['password'], $request->user()->password)) {
            throw ValidationException::withMessages(['password' => 'Please enter the staff account password.'])->errorBag('tabletUnlock');
        }
        $request->session()->forget('consent_tablet');
        $request->session()->regenerate();

        return redirect()->route('studio.appointments.index');
    }

    public function show(Request $request, ConsentForm $consent)
    {
        abort_unless($consent->user_id === $request->user()->id || in_array($request->user()->user_type, ['staff', 'owner', 'administrator'], true), 403);

        return view('member.consents.show', ['consent' => $consent->load(['client', 'service', 'appointment', 'recorder'])]);
    }
}
