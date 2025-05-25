<?php

declare(strict_types=1);

use Pokio\Promise;

test('no catch for correct throwable type throws exception', function (): void {
    expect(function () {
        $promise = (new Promise(function (): void {
            throw new RuntimeException('Uncaught exception');
        }))->catch(function (InvalidArgumentException $th): bool {
            return true;
        });

        $promise->defer();
        $promise->resolve();
    })->toThrow(RuntimeException::class, 'Uncaught exception');
})->with('runtimes');

test('catch for correct throwable type handles exception', function (): void {
    $promise = (new Promise(function (): void {
        throw new InvalidArgumentException('Caught exception');
    }))->catch(function (InvalidArgumentException $th): bool {
        return true;
    });

    $promise->defer();
    $result = $promise->resolve();
    expect($result)->toBeTrue();
})->with('runtimes');

test('can cancel a promise before it starts', function () {
    $executed = false;
    $promise = new Promise(function () use (&$executed) {
        $executed = true;
        return 'result';
    });

    expect($promise->cancel())->toBeTrue();
    expect($executed)->toBeFalse();
});

test('can cancel a promise after it starts', function () {
    $executed = false;
    $promise = new Promise(function () use (&$executed) {
        $executed = true;
        return 'result';
    });

    $promise->defer();
    expect($promise->cancel())->toBeTrue();
    expect($executed)->toBeFalse();
});

test('cannot resolve a cancelled promise', function () {
    $promise = new Promise(function () {
        return 'result';
    });

    $promise->cancel();
    expect(fn () => $promise->resolve())->toThrow(\RuntimeException::class);
});

test('cannot cancel a promise twice', function () {
    $promise = new Promise(function () {
        return 'result';
    });

    expect($promise->cancel())->toBeTrue();
    expect($promise->cancel())->toBeFalse();
});

test('cancellation propagates through promise chain', function () {
    $executed = false;
    $promise = new Promise(function () use (&$executed) {
        $executed = true;
        return 'result';
    });

    $chainedPromise = $promise->then(function ($result) {
        return $result . ' modified';
    });

    $chainedPromise->cancel();
    expect($executed)->toBeFalse();
});
