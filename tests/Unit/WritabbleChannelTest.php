<?php

declare(strict_types=1);
use Pokio\Channel\Channel;
use Pokio\Channel\ReadableChannel;
use Pokio\Channel\WritableChannel;

/**
 * @return array{WritableChannel, ReadableChannel}
 */
function writablePair(): array
{
    return (new Channel())->split();
}

it('writes a message that can be received by its read end', function (): void {
    [$tx, $rx] = writablePair();

    $tx->send('foo');

    expect($rx->recv())->toBe('foo');
});

it('WritableChannel has no recv method', function (): void {
    [$tx] = writablePair();

    expect(fn () => $tx->recv())->toThrow(Error::class);
});

it(/**
 * @throws ReflectionException
 */ 'throws when sending on a closed channel', closure: function (): void {
    [$tx, $rx] = writablePair();
    $rx->recv();

    /** @var Channel $base */
    $base = (new ReflectionClass($tx))
        ->getConstructor()?->getParameters()[0]?->getDeclaringClass()
        ?: null;

    $chan = new Channel();
    [$tx2] = $chan->split();
    $chan->close();

    expect(fn () => $tx2->send('bar'))
        ->toThrow(RuntimeException::class);
});
