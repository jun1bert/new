<?php

namespace App\Http\Controllers;

use App\Models\StaffAttendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function clockIn(Request $request)
    {
        abort_unless($request->user()->user_type === 'staff', 403);
        if ((int) $request->session()->get('attendance_qr_expires', 0) < now()->timestamp) return redirect()->route('attendance.qr.confirm');
        DB::transaction(function () use ($request) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if (StaffAttendance::where('user_id', $request->user()->id)->whereNull('clocked_out_at')->exists()) {
                throw ValidationException::withMessages(['attendance' => 'You are already timed in.']);
            }
            StaffAttendance::create(['user_id' => $request->user()->id, 'clocked_in_at' => now()]);
        });
        $request->session()->forget('attendance_qr_expires');
        return redirect()->route('dashboard')->with('status', 'You are timed in.');
    }

    public function clockOut(Request $request)
    {
        abort_unless($request->user()->user_type === 'staff', 403);
        if ((int) $request->session()->get('attendance_qr_expires', 0) < now()->timestamp) return redirect()->route('attendance.qr.confirm');
        DB::transaction(function () use ($request) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $attendance = StaffAttendance::where('user_id', $request->user()->id)->whereNull('clocked_out_at')->lockForUpdate()->first();
            if (! $attendance) throw ValidationException::withMessages(['attendance' => 'You are not currently timed in.']);
            $attendance->update(['clocked_out_at' => now()]);
        });
        $request->session()->forget('attendance_qr_expires');
        return redirect()->route('dashboard')->with('status', 'You are timed out.');
    }

    public function index(Request $request)
    {
        $data = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);
        $date = Carbon::parse($data['date'] ?? now('Asia/Manila')->toDateString(), 'Asia/Manila')->startOfDay();
        $start = $date->copy()->timezone(config('app.timezone'));
        $end = $date->copy()->addDay()->timezone(config('app.timezone'));
        return view('admin.attendance.index', [
            'date' => $date,
            'records' => StaffAttendance::with(['staff', 'changes.actor'])->where('clocked_in_at', '<', $end)->where(fn ($q) => $q->whereNull('clocked_out_at')->orWhere('clocked_out_at', '>=', $start))->orderByDesc('clocked_in_at')->paginate(20)->withQueryString(),
            'staffMembers' => User::where('user_type', 'staff')->orderBy('name')->get(),
            'presentStaff' => User::where('user_type', 'staff')->whereHas('attendances', fn ($q) => $q->whereNull('clocked_out_at'))->orderBy('name')->get(),
        ]);
    }

    public function storeCorrection(Request $request)
    {
        $data = $this->validateCorrection($request, true);
        DB::transaction(function () use ($request, $data) {
            User::whereKey($data['user_id'])->lockForUpdate()->firstOrFail();
            [$in, $out] = $this->times($data);
            $this->checkOverlap((int) $data['user_id'], $in, $out);
            $attendance = StaffAttendance::create(['user_id' => $data['user_id'], 'clocked_in_at' => $in, 'clocked_out_at' => $out]);
            $attendance->changes()->create(['actor_id' => $request->user()->id, 'reason' => $data['reason'], 'before' => null, 'after' => $this->snapshot($attendance)]);
        });
        return back()->with('status', 'Attendance record added.');
    }

    public function updateCorrection(Request $request, StaffAttendance $attendance)
    {
        $data = $this->validateCorrection($request, false);
        DB::transaction(function () use ($request, $attendance, $data) {
            User::whereKey($attendance->user_id)->lockForUpdate()->firstOrFail();
            $attendance = StaffAttendance::whereKey($attendance->id)->lockForUpdate()->firstOrFail();
            [$in, $out] = $this->times($data);
            $this->checkOverlap($attendance->user_id, $in, $out, $attendance->id);
            $before = $this->snapshot($attendance);
            $attendance->update(['clocked_in_at' => $in, 'clocked_out_at' => $out]);
            $attendance->changes()->create(['actor_id' => $request->user()->id, 'reason' => $data['reason'], 'before' => $before, 'after' => $this->snapshot($attendance)]);
        });
        return back()->with('status', 'Attendance correction saved.');
    }

    private function validateCorrection(Request $request, bool $creating): array
    {
        return $request->validate([
            'user_id' => [$creating ? 'required' : 'nullable', Rule::exists('users', 'id')->where('user_type', 'staff')],
            'clocked_in_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'clocked_out_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'reason' => ['required', 'string', 'max:500'],
        ]);
    }

    private function times(array $data): array
    {
        $in = Carbon::createFromFormat('Y-m-d\TH:i', $data['clocked_in_at'], 'Asia/Manila')->timezone(config('app.timezone'));
        $out = !empty($data['clocked_out_at']) ? Carbon::createFromFormat('Y-m-d\TH:i', $data['clocked_out_at'], 'Asia/Manila')->timezone(config('app.timezone')) : null;
        if ($in->isFuture() || ($out && ($out->isFuture() || $out->lessThanOrEqualTo($in)))) {
            throw ValidationException::withMessages(['clocked_out_at' => 'Use past times, with time out after time in.']);
        }
        return [$in, $out];
    }

    private function checkOverlap(int $userId, Carbon $in, ?Carbon $out, ?int $except = null): void
    {
        $overlap = StaffAttendance::where('user_id', $userId)->when($except, fn ($q) => $q->whereKeyNot($except))
            ->where(fn ($q) => $q->whereNull('clocked_out_at')->orWhere('clocked_out_at', '>', $in))
            ->when($out, fn ($q) => $q->where('clocked_in_at', '<', $out))->exists();
        if ($overlap) throw ValidationException::withMessages(['clocked_in_at' => 'This attendance overlaps an existing record.']);
    }

    private function snapshot(StaffAttendance $attendance): array
    {
        return ['clocked_in_at' => $attendance->clocked_in_at?->toIso8601String(), 'clocked_out_at' => $attendance->clocked_out_at?->toIso8601String()];
    }
}
