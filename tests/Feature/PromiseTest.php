<?php

declare(strict_types=1);

use Pokio\Exceptions\FutureAlreadyAwaited;
use Pokio\UnwaitedFutureManager;

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
    $path = tempnam(sys_get_temp_dir(), 'pokio_');

    $promise = async(fn () => 42)
        ->finally(function () use (&$path): void {
            file_put_contents($path, 'called');
        });

    $result = await($promise);

    expect($result)->toBe(42);
    expect(file_get_contents($path))->toBe('called');
})->with('runtimes');

test('finally is called after exception', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'pokio_');

    $promise = async(function () {
        throw new RuntimeException('Exception 1');
    })->finally(function () use (&$path): void {
        file_put_contents($path, 'called');
    });

    expect(function () use ($promise): void {
        await($promise);
    })->toThrow(RuntimeException::class, 'Exception 1');

    expect(file_get_contents($path))->toBe('called');
})->with('runtimes');

test('finally is called after then', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'pokio_');

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
})->with('runtimes');

test('then after async returning a promise', function (): void {
    $promise = async(fn () => async(fn () => 4))
        ->then(fn (int $result) => $result * 2);

    $result = await($promise);

    expect($result)->toBe(8);
})->with('runtimes');

test('second await uses throws an exception', function (): void {
    $promise = async(fn (): int => 1 + 2)
        ->then(fn (int $result): int => $result * 2);

    $result = await($promise);

    expect($result)->toBe(6)
        ->and(fn () => await($promise))
        ->toThrow(FutureAlreadyAwaited::class);
})->with('runtimes');

test('promises are always waited for', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'pokio_');

    // create file:
    file_put_contents($path, 'start: ');

    async(function () use (&$path): void {
        file_put_contents($path, 'a called by callback, ', FILE_APPEND);
    })->then(function () use (&$path): void {
        // append to the file
        file_put_contents($path, 'a called by then, ', FILE_APPEND);
    })->finally(function () use (&$path): void {
        // append to the file
        file_put_contents($path, 'a called by finally.', FILE_APPEND);
    });

    async(function () use (&$path): void {
        file_put_contents($path, 'b called by callback, ', FILE_APPEND);
    })->then(function () use (&$path): void {
        // append to the file
        file_put_contents($path, 'b called by then, ', FILE_APPEND);
    })->finally(function () use (&$path): void {
        // append to the file
        file_put_contents($path, 'b called by finally.', FILE_APPEND);
    });

    async(function () use (&$path) {
        async(fn () => file_put_contents($path, 'c called by callback, ', FILE_APPEND));
    })->then(function () use (&$path) {
        // append to the file
        async(fn () => file_put_contents($path, 'c called by then, ', FILE_APPEND));
    })->finally(function () use (&$path) {
        // append to the file
        async(fn () => file_put_contents($path, 'c called by finally.', FILE_APPEND));
    });

    gc_collect_cycles();

    UnwaitedFutureManager::instance()->run();

    expect(file_get_contents($path))->toBe('start: a called by callback, a called by then, a called by finally.b called by callback, b called by then, b called by finally.c called by callback, c called by then, c called by finally.');
})->with('runtimes');

test('invokable promise resolves correctly', function (): void {
    $result = async(fn (): int => 1 + 2)();

    expect($result)->toBe(3);
})->with('runtimes');

test('invokable waits for the promise to resolve', function (): void {
    $promise = async(fn (): int => 1 + 2);

    $result = $promise();

    expect($result)->toBe(3);
})->with('runtimes');

test('invokable promise with await resolves correctly', function (): void {
    $promise = async(fn (): int => 1 + 2);

    $result = $promise(true);

    expect($result)->toBe(3);
})->with('runtimes');

test('invokable promise without await does not resolve', function (): void {
    $promise = async(fn (): int => 1 + 2);

    $result = $promise(false);

    expect($result)->toBeNull();
})->with('runtimes');
