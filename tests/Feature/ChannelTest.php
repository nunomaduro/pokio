<?php

declare(strict_types=1);

use Pokio\Channel\Channel;
use Pokio\Exceptions\FutureAlreadyAwaited;
use Pokio\Kernel;

beforeAll(function (): void {
    if (! extension_loaded('pcntl')) {
        test()->markTestSkipped('pcntl extension missing');
    }

    Kernel::instance()->useFork(16);
});

test('single message crosses the channel via forked task', function (): void {
    $ch = new Channel();

    $tx = async(fn () => $ch->send('ping'));
    $rx = async(fn () => $ch->recv());

    [$void, $received] = await([$tx, $rx]);

    expect($received)->toBe('ping');
});

test('awaiting a promise twice throws', function (): void {
    $promise = async(fn () => 42);

    expect(await($promise))->toBe(42)
        ->and(fn () => await($promise))
        ->toThrow(FutureAlreadyAwaited::class);

});

test('reading from a closed channel throws', function (): void {
    $ch = new Channel();
    $ch->close();

    expect(fn () => $ch->recv())
        ->toThrow(RuntimeException::class);
});
