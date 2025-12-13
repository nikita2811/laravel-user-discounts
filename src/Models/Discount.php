<?php

namespace Nikita\LaravelUserDiscounts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'percentage',
        'active',
        'expires_at',
        'usage_limit_per_user',
    ];
    public function userDiscounts()
    {
        return $this->hasMany(UserDiscount::class);
    }
}
