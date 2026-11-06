<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Client extends Model
{
    use HasFactory, SoftDeletes, BelongsToUser;

    protected $fillable = [
        'user_id',
        'name',
        'search_name',
        'phone_e164',
        'document_type',
        'document_number',
        'email',
        'notes',
    ];

    protected static function booted(): void
    {
        static::saving(function (Client $client) {
            if (! empty($client->name)) {
                $client->search_name = Str::lower(Str::ascii($client->name));
            }
        });
    }
}
