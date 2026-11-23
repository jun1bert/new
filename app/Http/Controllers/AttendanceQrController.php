<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AttendanceQrController extends Controller
{
    public function display()
    {
        return response()->view('admin.attendance.qr')->header('Cache-Control', 'no-store, private');
    }

    public function current()
    {
        $slot = intdiv(now()->timestamp, 30);
        $signature = hash_hmac('sha256', (string) $slot, config('app.key'));
        $url = rtrim(config('app.url'), '/').route('attendance.qr.scan', [], false).'?'.http_build_query(['slot' => $slot, 'code' => $signature]);
        return response()->json(['url' => $url, 'refresh_in' => 30 - now()->timestamp % 30])->header('Cache-Control', 'no-store, private');
    }

    public function scan(Request $request)
    {
        $slot = $request->query('slot');
        $code = $request->query('code');
        if (! is_string($slot) || ! ctype_digit($slot) || ! is_string($code) || (int) $slot !== intdiv(now()->timestamp, 30) || ! hash_equals(hash_hmac('sha256', $slot, config('app.key')), $code)) {
            return response()->view('attendance.expired', [], 410)->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
        }
        // The code must be scanned while current; the same browser then has time to sign in.
        $request->session()->put('attendance_qr_expires', now()->addMinutes(5)->timestamp);
        return redirect()->route('attendance.qr.confirm')->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }

    public function confirm(Request $request)
    {
        abort_unless($request->user()->user_type === 'staff', 403);
        if ((int) $request->session()->get('attendance_qr_expires', 0) < now()->timestamp) {
            return response()->view('attendance.expired', [], 410)->header('Cache-Control', 'no-store, private');
        }
        return response()->view('attendance.confirm', ['activeAttendance' => $request->user()->attendances()->whereNull('clocked_out_at')->first()])->header('Cache-Control', 'no-store, private');
    }
}
