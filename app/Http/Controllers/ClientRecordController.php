<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;
use Illuminate\Http\Request;

class ClientRecordController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        return view('studio.clients.index', [
            'clients' => User::query()
                ->where('user_type', 'member')
                // Do not show empty, system-created guest placeholders as client records.
                ->where(fn ($q) => $q
                    ->where('email', 'not like', 'guest-%@booking.local')
                    ->orWhereHas('appointments')
                    ->orWhereHas('consentForms'))
                ->when($search, fn($q) => $q->where(fn($q) => $q->where('name','like',"%{$search}%")->orWhere('email','like',"%{$search}%")))
                ->withCount(['appointments', 'consentForms'])
                ->orderBy('name')
                ->paginate(20)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function show(User $client): View
    {
        abort_unless($client->user_type === 'member', 404);

        return view('studio.clients.show', [
            'client' => $client,
            'appointments' => $client->appointments()->with(['service', 'staff'])->latest('scheduled_at')->get(),
            'consentForms' => $client->consentForms()->with(['service', 'recorder'])->latest('signed_at')->get(),
        ]);
    }
}
