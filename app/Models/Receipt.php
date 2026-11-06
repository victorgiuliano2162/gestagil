<?php

namespace App\Models;

use App\Support\AmountInWords;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'transaction_id',
        'client_id',
        'code',
        'year',
        'number',
        'status',
        'issued_at',
        'amount_cents',
        'payment_method',
        'description',
        'payer_name',
        'payer_document',
        'issuer_snapshot',
        'canceled_at',
        'cancel_reason',
    ];

    protected $appends = [
        'formatted_number',
        'formatted_amount',
        'amount_in_words',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'number' => 'integer',
            'amount_cents' => 'integer',
            'issued_at' => 'datetime',
            'canceled_at' => 'datetime',
            'issuer_snapshot' => 'array',
        ];
    }

    protected function formattedNumber(): Attribute
    {
        return Attribute::make(
            get: fn () => sprintf('%04d/%04d', $this->number, $this->year)
        );
    }

    protected function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => 'R$ ' . number_format($this->amount_cents / 100, 2, ',', '.')
        );
    }

    protected function amountInWords(): Attribute
    {
        return Attribute::make(
            get: fn () => AmountInWords::format($this->amount_cents)
        );
    }

    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }
}
