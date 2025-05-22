<?php

declare(strict_types=1);

test('async with a recursive promise', function (): void {
    $promise = async(fn (): Pokio\Promise => async(fn (): Pokio\Promise => async(fn (): Pokio\Promise => async(fn (): Pokio\Promise => async(fn (): int => 1)))));

    $result = await($promise);

    expect($result)->toBe(1);
})->with('runtimes');

test('async with a recursive promise with multiple awaits', function (): void {
    $promiseA = async(fn (): Pokio\Promise => async(fn (): Pokio\Promise => async(fn (): Pokio\Promise => async(fn (): Pokio\Promise => async(fn (): int => 1)))));
    $promiseB = async(fn (): Pokio\Promise => async(fn (): Pokio\Promise => async(fn (): Pokio\Promise => async(fn (): Pokio\Promise => async(fn (): int => 2)))));

    [$resultA, $resultB] = await([$promiseA, $promiseB]);

    expect($resultA)->toBe(1)
        ->and($resultB)->toBe(2);
})->with('runtimes');

test('async with a recursive promise with multiple awaits and a single await', function (): void {
    $promise = async(fn (): Pokio\Promise => async(fn (): Pokio\Promise => async(function () {
        $promiseA = async(fn (): Pokio\Promise => async(fn (): Pokio\Promise => async(fn (): Pokio\Promise => async(fn (): Pokio\Promise => async(fn (): int => 1)))));
        $promiseB = async(fn (): Pokio\Promise => async(fn (): Pokio\Promise => async(fn (): Pokio\Promise => async(fn (): Pokio\Promise => async(fn (): int => 2)))));

        return await([$promiseA, $promiseB]);
    })));

    $result = await($promise);

    expect($result)->toBe([1, 2]);
})->with('runtimes');
