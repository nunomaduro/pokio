<?php

declare(strict_types=1);

use Pokio\Promise;

test('no catch for correct throwable type throws exception', function (): void {
    expect(function (): void {
        $promise = (new Promise(function (): void {
            throw new RuntimeException('Uncaught exception');
        }))->catch(function (InvalidArgumentException $th): bool {
            return true;
        });

        $promise->defer();
        $promise->resolve();
    })->toThrow(RuntimeException::class, 'Uncaught exception');
})->with('runtimes');

test('Promise rethrows exception when catch block types do not match the thrown exception', function (): void {
    expect(function (): void {
        $promise = (new Promise(function (): void {
            throw new RuntimeException('Uncaught exception');
        }))->catch(function (InvalidArgumentException|LogicException $th): bool {
            return true;
        });

        $promise->defer();
        $promise->resolve();
    })->toThrow(RuntimeException::class, 'Uncaught exception');
})->with('runtimes');

test('Promise catches exception when catch block types include the thrown exception', function (): void {
    $promise = (new Promise(function (): void {
        throw new RuntimeException('Uncaught exception');
    }))->catch(function (InvalidArgumentException|LogicException|RuntimeException $th): bool {
        return true;
    });

    $promise->defer();
    $result = $promise->resolve();
    expect($result)->toBeTrue();
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
