<?php

namespace Nikita\LaravelUserDiscounts\Services;

use Nikita\LaravelUserDiscounts\Models\Discount;
use Nikita\LaravelUserDiscounts\Models\UserDiscount;
use Nikita\LaravelUserDiscounts\Models\DiscountAudit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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

            $eligible = $eligible->sortBy(
                fn($d) =>
                config('discounts.stacking_order') === 'asc'
                    ? $d->discount->percentage
                    : -$d->discount->percentage
            );
            $percentages = $eligible
                ->pluck('discount.percentage')
                ->toArray();

            $result = $this->calculateFinalAmount(
                $amount,
                $percentages,
                config('discounts.max_percentage_cap')
            );
            foreach ($eligible as $userDiscount) {
                if ($result['total_applied'] < $userDiscount->discount->percentage) {
                    break;
                }

                UserDiscount::where('id', $userDiscount->id)->update([
                    'usage_count' => DB::raw('usage_count + 1'),
                ]);
                event(new DiscountApplied($user, $userDiscount->discount));

                $result['total_applied'] -= $userDiscount->discount->percentage;
            }

            foreach ($eligible as $userDiscount) {
                DiscountAudit::create([
                    'user_id' => $user->id,
                    'discount_id' => $userDiscount->discount->id,
                    'applied_percentage' => $userDiscount->discount->percentage,
                    'amount_before' => $amount,
                    'amount_after' => $result['final_amount'],
                ]);
            }
            return $result['final_amount'];
        });
    }

    public function revoke(User $user, Discount $discount)
    {
        $revoked = UserDiscount::where('user_id', $user->id)
            ->where('discount_id', $discount->id)
            ->update(['revoked' => true]);


        event(new DiscountRevoked($user, $discount));
        return $revoked;
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
    public function calculateFinalAmount(
        float $amount,
        array $percentages,
        int $maxCap,
        int $roundingMode = PHP_ROUND_HALF_UP
    ): array {
        $totalApplied = 0;

        foreach ($percentages as $percentage) {
            if ($totalApplied + $percentage > $maxCap) {
                break;
            }

            $totalApplied += $percentage;
        }

        $final = $amount * (1 - ($totalApplied / 100));

        return [
            'total_applied' => $totalApplied,
            'final_amount' => round($final, 2, $roundingMode),
        ];
    }
}
