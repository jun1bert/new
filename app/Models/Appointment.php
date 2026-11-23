<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Appointment extends Model
{
    public ?string $historyReason = null;

    protected static function booted(): void
    {
        static::created(fn (Appointment $appointment) => $appointment->recordChange('created'));
        static::updated(function (Appointment $appointment) {
            if ($appointment->wasChanged(['status', 'scheduled_at', 'provider_id', 'duration_minutes'])) {
                $action = $appointment->wasChanged('status') ? $appointment->status
                    : ($appointment->wasChanged('scheduled_at') ? 'rescheduled' : 'provider_changed');
                $appointment->recordChange($action);
            }
        });
    }

    private function recordChange(string $action): void
    {
        $fields = ['status', 'scheduled_at', 'provider_id', 'duration_minutes'];
        $before = $action === 'created' ? null : \Illuminate\Support\Arr::only($this->getRawOriginal(), $fields);
        $after = \Illuminate\Support\Arr::only($this->getAttributes(), $fields);
        $after['provider_name'] = User::find($after['provider_id'] ?? null)?->name;
        if ($before !== null) $before['provider_name'] = User::find($before['provider_id'] ?? null)?->name;
        $this->changeHistory()->create(['actor_id' => auth()->id(), 'action' => $action, 'reason' => $this->historyReason, 'before' => $before, 'after' => $after]);
    }

    public function changeHistory(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AppointmentChange::class)->latest('id');
    }

    public function courseSessions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'treatment_course_id', 'treatment_course_id');
    }

    public function getNextSessionAttribute(): ?self
    {
        return $this->relationLoaded('courseSessions')
            ? $this->courseSessions->firstWhere('session_number', $this->session_number + 1)
            : $this->courseSessions()->where('session_number', $this->session_number + 1)->first();
    }

    public function getCanScheduleNextAttribute(): bool
    {
        return $this->status === 'completed' && $this->session_number < $this->session_count && $this->next_session === null;
    }
    protected $fillable = [
        'treatment_course_id', 'user_id', 'service_id', 'staff_id',
        'session_number', 'scheduled_at', 'contact_number', 'booking_email', 'notes', 'status', 'completed_at', 'provider_id', 'duration_minutes',
    ];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function client(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function service(): BelongsTo { return $this->belongsTo(Service::class); }
    public function services(): BelongsToMany { return $this->belongsToMany(Service::class)->withPivot('session_count')->orderBy('services.id'); }

    public function getBookedServicesAttribute(): Collection
    {
        return $this->services->isNotEmpty() ? $this->services : collect([$this->service])->filter();
    }

    public function getServiceNamesAttribute(): string { return $this->booked_services->pluck('name')->implode(', '); }
    public function getServiceTotalCentsAttribute(): int { return $this->booked_services->sum(fn ($service) => (int) round((float) $service->price * 100)); }
    public function getTotalDurationAttribute(): ?int
    {
        return $this->booked_services->contains(fn ($service) => !$service->duration) ? null : $this->booked_services->sum('duration');
    }
    public function getSessionCountAttribute(): int { return (int) $this->booked_services->max(fn ($service) => $service->pivot?->session_count ?? $service->session_count); }
    public function getSessionSummaryAttribute(): string
    {
        return $this->booked_services->map(fn ($service) => $service->name.': session '.$this->session_number.' of '.($service->pivot?->session_count ?? $service->session_count))->implode(' · ');
    }

    // A single signed snapshot contains the wording for every treatment requiring consent.
    public function getConsentServiceAttribute(): ?Service
    {
        $services = $this->booked_services->filter->requires_consent;
        if ($services->isEmpty()) { return null; }
        $combined = clone $services->first();
        $combined->name = $services->pluck('name')->implode(', ');
        $combined->consent_content = $services->contains(fn ($service) => blank($service->consent_content))
            ? null
            : ($services->count() === 1 ? $services->first()->consent_content : $services->map(fn ($service) => $service->name."\n\n".$service->consent_content)->implode("\n\n──────────\n\n"));
        return $combined;
    }
    public function staff(): BelongsTo { return $this->belongsTo(User::class, 'staff_id'); }
    public function provider(): BelongsTo { return $this->belongsTo(User::class, 'provider_id'); }
    public function invoice(): HasOne { return $this->hasOne(TreatmentInvoice::class, 'treatment_course_id', 'treatment_course_id'); }
    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(Payment::class); }
    public function payment(): HasOne { return $this->hasOne(Payment::class); }
    public function consentForm(): HasOne { return $this->hasOne(ConsentForm::class); }
    public function skinAnalysisPhotos(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(SkinAnalysisPhoto::class); }
    public function supplyUsages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InventoryMovement::class)->where('type', 'treatment_usage');
    }
}


