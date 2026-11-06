<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TransactionCategory extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'type',
        'name',
        'name_key',
        'color',
    ];

    protected static function booted(): void
    {
        static::saving(function (TransactionCategory $category) {
            if (empty($category->name_key) && ! empty($category->name)) {
                $category->name_key = Str::slug($category->name);
            }
        });
    }
}
