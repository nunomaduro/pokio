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

        $promise->resolve();
    })->toThrow(RuntimeException::class, 'Uncaught exception');
})->with('runtimes');

test('catch for correct throwable type handles exception', function (): void {
    $promise = (new Promise(function (): void {
        throw new InvalidArgumentException('Caught exception');
    }))->catch(function (InvalidArgumentException $th): bool {
        return true;
    });

    $result = $promise->resolve();
    expect($result)->toBeTrue();
})->with('runtimes');
