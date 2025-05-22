<?php

declare(strict_types=1);

test('Promise::all resolves all promises', function (): void {
    $promises = [
        async(fn (): int => 1),
        async(fn (): int => 2),
        async(fn (): int => 3),
    ];
    $result = await(Pokio\Promise::all($promises));
    expect($result)->toBe([1, 2, 3]);
})->with('runtimes');

test('Promise::all rejects on first failure', function (): void {
    $promises = [
        async(fn (): int => 1),
        async(fn () => throw new RuntimeException('fail')),
        async(fn (): int => 3),
    ];
    expect(fn (): mixed => await(Pokio\Promise::all($promises)))->toThrow(RuntimeException::class);
})->with('runtimes');

test('Promise::any resolves on first success', function (): void {
    $promises = [
        async(fn () => throw new RuntimeException('fail')),
        async(fn (): int => 42),
        async(fn (): int => 99),
    ];
    $result = await(Pokio\Promise::any($promises));
    expect($result)->toBe(42);
})->with('runtimes');

test('Promise::any throws if all reject', function (): void {
    $promises = [
        async(fn () => throw new RuntimeException('fail1')),
        async(fn () => throw new RuntimeException('fail2')),
    ];
    expect(fn (): mixed => await(Pokio\Promise::any($promises)))->toThrow(RuntimeException::class);
})->with('runtimes');

test('Promise::race resolves on first resolve', function (): void {
    $promises = [
        async(fn (): int => 7),
        async(fn (): int => 8),
    ];
    $result = await(Pokio\Promise::race($promises));
    expect([7, 8])->toContain($result);
})->with('runtimes');

test('Promise::race rejects on first reject', function (): void {
    $promises = [
        async(fn () => throw new RuntimeException('fail')),
        async(fn (): int => 2),
    ];
    expect(fn (): mixed => await(Pokio\Promise::race($promises)))->toThrow(RuntimeException::class);
})->with('runtimes');

test('Promise::race throws if no promises', function (): void {
    expect(fn (): mixed => await(Pokio\Promise::race([])))->toThrow(RuntimeException::class);
})->with('runtimes');
