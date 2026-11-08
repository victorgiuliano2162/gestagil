<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Traits\BelongsToUser;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use BelongsToUser, HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'type',
        'amount_cents',
        'occurred_on',
        'description',
        'category_id',
        'payment_method',
        'appointment_id',
        'client_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'occurred_on' => 'date',
            'amount_cents' => 'integer',
            'payment_method' => PaymentMethod::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'category_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class)->withTrashed();
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }
}
