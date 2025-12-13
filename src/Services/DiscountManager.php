<?php

namespace Nikita\LaravelUserDiscounts\Services;

use Nikita\LaravelUserDiscounts\Models\Discount;
use Nikita\LaravelUserDiscounts\Models\UserDiscount;
use Nikita\LaravelUserDiscounts\Models\DiscountAudit;
use App\Models\User;

use Nikita\LaravelUserDiscounts\Events\DiscountApplied;
use Nikita\LaravelUserDiscounts\Events\DiscountAssigned;
use Nikita\LaravelUserDiscounts\Events\DiscountRevoked;


use Nikita\LaravelUserDiscounts\Contracts\DiscountManagerContract;


class DiscountManager implements DiscountManagerContract
{
    public function eligibleFor(User $user)
    {
        return UserDiscount::with('discount')
            ->where('user_id', $user->id)
            ->where('revoked', false)
            ->whereHas('discount', function ($q) {
                $q->where('active', true)
                    ->where(function ($x) {
                        $x->whereNull('expires_at')->orWhere(
                            'expires_at',
                            '>',
                            now()
                        );
                    });
            })
            ->get()
            ->filter(function ($userDiscount) {
                if ($userDiscount->discount->usage_limit_per_user == 0) return true;
                return $userDiscount->usage_count < $userDiscount->discount->usage_limit_per_user;
            });
    }

    public function apply(User $user, $amount)
    {
        return DB::transaction(function () use ($user, $amount) {
            $eligible = $this->eligibleFor($user);
            // Sort based on stacking config
            $eligible = $eligible->sortBy(fn($d) =>
            config('discounts.stacking_order') === 'asc'
                ? $d->discount->percentage
                : -$d->discount->percentage);
            $original = $amount;
            $totalApplied = 0;
            foreach ($eligible as $ud) {
                $discount = $ud->discount->percentage;
                if (
                    $totalApplied + $discount >
                    config('discounts.max_percentage_cap')
                ) {
                    break; // do not exceed global cap
                }
                $totalApplied += $discount;
                // Increment usage atomically
                UserDiscount::where('id', $ud->id)->update([
                    'usage_count' => DB::raw('usage_count + 1'),
                ]);
                event(new DiscountApplied($user, $ud->discount));
            }
            // Apply discount
            $final = $amount * (1 - ($totalApplied / 100));
            $final = round($final, 2, config('discounts.rounding'));
            // Audit
            foreach ($eligible as $ud) {
                DiscountAudit::create([
                    'user_id' => $user->id,
                    'discount_id' => $ud->discount->id,
                    'applied_percentage' => $ud->discount->percentage,
                    'amount_before' => $original,
                    'amount_after' => $final
                ]);
            }
            return $final;
        });
    }

    public function revoke(User $user, Discount $discount)
    {
        UserDiscount::where('user_id', $user->id)
            ->where('discount_id', $discount->id)
            ->update(['revoked' => true]);
        event(new DiscountRevoked($user, $discount));
    }

    public function assign(User $user, Discount $discount)
    {
        $userDiscount = UserDiscount::firstOrCreate([
            'user_id' => $user->id,
            'discount_id' => $discount->id,
        ]);
        event(new DiscountAssigned($user, $discount));

        return $userDiscount;
    }
}
