<?php

declare(strict_types=1);

use Pokio\Channel\Channel;
use Pokio\Channel\ReadableChannel;
use Pokio\Channel\WritableChannel;

/**
 * @return array{WritableChannel, ReadableChannel}
 */
function readablePair(): array
{
    return (new Channel())->split();
}

it('reads a message sent by its write end', function (): void {
    [$tx, $rx] = readablePair();

    $tx->send(42);

    expect($rx->recv())->toBe(42);
});

it('ReadableChannel has no send method', function (): void {
    [, $rx] = readablePair();

    expect(fn () => $rx->send('oops'))->toThrow(Error::class);
});

it('throws when reading from a closed channel', function (): void {
    [$tx, $rx] = readablePair();
    $tx->send('baz');

    $chan = new Channel();
    [, $rx2] = $chan->split();
    $chan->close();

    expect(fn () => $rx2->recv())
        ->toThrow(RuntimeException::class);
});
