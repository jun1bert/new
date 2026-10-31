<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $attributes = ['is_active' => true, 'user_type' => 'member'];

    protected static function booted(): void
    {
        static::updated(function (User $user) {
            if ($user->wasChanged(['user_type', 'is_active']) && ($user->user_type !== 'staff' || !$user->is_active)) {
                Appointment::where('provider_id', $user->id)->whereIn('status', ['pending', 'scheduled'])->each(function (Appointment $appointment) {
                    $appointment->historyReason = 'Provider is no longer an active staff member.';
                    $appointment->update(['provider_id' => null]);
                });
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'user_type',
            'is_active',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function appointments(): HasMany { return $this->hasMany(Appointment::class); }
    public function consentForms(): HasMany { return $this->hasMany(ConsentForm::class); }
    public function claimRequests(): HasMany { return $this->hasMany(ClientClaimRequest::class, 'claimant_id'); }
}
