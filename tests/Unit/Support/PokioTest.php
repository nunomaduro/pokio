<?php

use Pokio\Support\Pokio;

test('spawn with a single task', function (): void {
    $handle = Pokio::spawn(fn(): int => 1 + 2);

    $result = $handle->await();

    expect($result)->toBe(3);
});

test('spawn with multiple tasks and join results', function (): void {
    $handleA = Pokio::spawn(fn(): int => 1 + 2);
    $handleB = Pokio::spawn(fn(): int => 3 + 4);

    [$resultA, $resultB] = Pokio::join([$handleA, $handleB]);

    expect($resultA)->toBe(3)
        ->and($resultB)->toBe(7);
});
