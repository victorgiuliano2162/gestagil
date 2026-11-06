<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes, BelongsToUser;

    protected $fillable = [
        'user_id',
        'client_id',
        'service_description',
        'service_at',
        'status',
        'technical_notes',
        'expected_amount_cents',
        'completed_at',
        'canceled_at',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'service_at' => 'datetime',
            'completed_at' => 'datetime',
            'canceled_at' => 'datetime',
            'status' => AppointmentStatus::class,
            'expected_amount_cents' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }
}
