<?php

namespace Nikita\LaravelUserDiscounts\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Nikita\LaravelUserDiscounts\Models\Discount;
use App\Models\User;

class DiscountRevoked
{
    use Dispatchable, SerializesModels;

    public $user;
    public $discount;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, Discount $discount)
    {
        $this->user = $user;
        $this->discount = $discount;
    }
}
