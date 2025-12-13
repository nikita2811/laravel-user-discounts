<?php

namespace Nikita\LaravelUserDiscounts\Contracts;

use App\Models\User;
use Nikita\LaravelUserDiscounts\Models\Discount;

interface DiscountManagerContract
{
    public function eligibleFor(User $user);

    public function assign(User $user, Discount $discount);

    public function revoke(User $user, Discount $discount);

    public function apply(User $user, $amount);
}
