<?php

declare(strict_types=1);

test('async with a single promise', function (): void {
    $promise = async(fn (): int => 1 + 2);

    $result = await($promise);

    expect($result)->toBe(3);
})->with('runtimes');

test('async with a multiple promises', function (): void {
    $promiseA = async(fn (): int => 1 + 2);

    $promiseB = async(fn (): int => 3 + 4);

    [$resultA, $resultB] = await([$promiseA, $promiseB]);

    expect($resultA)->toBe(3)
        ->and($resultB)->toBe(7);
})->with('runtimes');

test('async with a then', function (): void {
    $promise = async(fn (): int => 1 + 2)
        ->then(fn ($result): int => $result + 5);

    $result = await($promise);

    expect($result)->toBe(8);
})->with('runtimes');
