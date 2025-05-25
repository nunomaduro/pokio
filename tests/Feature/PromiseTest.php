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

test('async with single then callback', function (): void {
    $promise = async(fn (): int => 1 + 2)
        ->then(fn (int $result): int => $result * 2);

    $result = await($promise);

    expect($result)->toBe(6);
})->with('runtimes');

test('async with multiple then callbacks', function (): void {
    $promise = async(fn (): int => 1 + 2)
        ->then(fn (int $result): int => $result * 2)
        ->then(fn (int $result): int => $result - 1);

    $result = await($promise);

    expect($result)->toBe(5);
})->with('runtimes');

test('async with an exception and no catch throws exception', function (): void {
    expect(function (): void {
        $promise = async(function (): void {
            throw new RuntimeException('Exception 1');
        });

        await($promise);
    })->toThrow(RuntimeException::class, 'Exception 1');
})->with('runtimes');

test('async with a catch callback', function (): void {
    $promise = async(function (): void {
        throw new RuntimeException('Exception 1');
    })->catch(function (Throwable $th) use (&$caught): bool {
        expect($th)->toBeInstanceOf(RuntimeException::class)
            ->and($th->getMessage())->toBe('Exception 1');

        return true;
    });

    $called = await($promise);

    expect($called)->toBeTrue();
})->with('runtimes');

test('async with a catch callback that throws an exception', function (): void {
    $promise = async(function (): void {
        throw new RuntimeException('Exception 1');
    })->catch(function (Throwable $th): void {
        throw new RuntimeException('Exception 2');
    });

    expect(function () use ($promise): void {
        await($promise);
    })->toThrow(RuntimeException::class, 'Exception 2');
})->with('runtimes');

test('async with a finally callback', function (): void {
    $tmpfile = tmpfile();
    $path = stream_get_meta_data($tmpfile)['uri'];

    $promise = async(fn () => 42)
        ->finally(function () use (&$path): void {
            file_put_contents($path, 'called');
        });

    $result = await($promise);

    expect($result)->toBe(42);
    expect(file_get_contents($path))->toBe('called');

    fclose($tmpfile);
})->with('runtimes');

test('finally is called after exception', function (): void {
    $tmpfile = tmpfile();
    $path = stream_get_meta_data($tmpfile)['uri'];

    $promise = async(function () {
        throw new RuntimeException('Exception 1');
    })->finally(function () use (&$path): void {
        file_put_contents($path, 'called');
    });

    expect(function () use ($promise): void {
        await($promise);
    })->toThrow(RuntimeException::class, 'Exception 1');

    expect(file_get_contents($path))->toBe('called');

    fclose($tmpfile);
})->with('runtimes');

test('finally is called after then', function (): void {
    $tmpfile = tmpfile();
    $path = stream_get_meta_data($tmpfile)['uri'];

    $promise = async(fn (): int => 1 + 1)
        ->then(function (int $result) use (&$path): int {
            file_put_contents($path, 'called');

            return $result * 2;
        })
        ->finally(function () use (&$path): void {
            file_put_contents($path, 'called again');
        });

    $result = await($promise);

    expect($result)->toBe(4);
    expect(file_get_contents($path))->toBe('called again');

    fclose($tmpfile);
})->with('runtimes');

test('then after async returning a promise', function (): void {
    $promise = async(fn () => async(fn () => 4))
        ->then(fn (int $result) => $result * 2);

    $result = await($promise);

    expect($result)->toBe(8);
})->with('runtimes');

test('second await uses already resolved promise', function (): void {
    $promise = async(fn (): int => 1 + 2)
        ->then(fn (int $result): int => $result * 2);

    $result = await($promise);
    expect($result)->toBe(6);

    $result = await($promise);
    expect($result)->toBe(6);
})->with('runtimes');
