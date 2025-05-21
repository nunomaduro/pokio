<?php

declare(strict_types=1);

use Tests\Fixtures\Exceptions\HedgehogException;

test('async with an exception thrown', function (): void {
    expect(function (): void {
        $promise = async(function (): void {
            throw new HedgehogException('Not enough hedgehogs');
        });

        await($promise);
    })->toThrow(HedgehogException::class, 'Not enough hedgehogs');
})->with('runtimes');

test('async with a caught exception', function (): void {
    $promise = async(function (): void {
        throw new HedgehogException('Not enough hedgehogs');
    })->catch(function (Throwable $e): string {
        expect($e)->toBeInstanceOf(HedgehogException::class)
            ->and($e->getMessage())->toEqual('Not enough hedgehogs');

        return 'Hedgehogs';
    });

    $result = await($promise);

    expect($result)->toEqual('Hedgehogs');
})->with('runtimes');

test('async with a caught exception with finally', function (): void {
    $promise = async(function (): void {
        throw new HedgehogException('Not enough hedgehogs');
    })->catch(function (Throwable $e): string {
        expect($e)->toBeInstanceOf(HedgehogException::class)
            ->and($e->getMessage())->toEqual('Not enough hedgehogs');
    })->finally(fn (): string => 'Hedgehogs');

    $result = await($promise);

    expect($result)->toEqual('Hedgehogs');
})->with('runtimes');
