<?php

namespace Nikita\LaravelUserDiscounts\tests;

use Nikita\LaravelUserDiscounts\Services\DiscountManager;

it('calculates the final discounted amount and total percentage applied', function () {
    $manager = new DiscountManager();

    $result = $manager->calculateFinalAmount(
        amount: 1000,
        percentages: [10, 20, 30], // total = 60
        maxCap: 40
    );

    expect($result['total_applied'])->toBe(30);
    expect($result['final_amount'])->toBe(700.0);
});
