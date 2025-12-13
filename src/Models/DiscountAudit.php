<?php

namespace Nikita\LaravelUserDiscounts\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class DiscountAudit extends Model
{
    protected $fillable = [
        'user_id',
        'discount_id',
        'applied_percentage',
        'amount_before',
        'amount_after',
    ];
}
