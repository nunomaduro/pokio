<?php

declare(strict_types=1);

use Pokio\Channel\Channel;

it('sends and receives FIFO', function (): void {
    $c = new Channel();
    foreach (['a', 'b', 'c'] as $v) {
        $c->send($v);
    }
    foreach (['a', 'b', 'c'] as $v) {
        expect($c->recv())->toBe($v);
    }
});

it('split() returns independent ends', function (): void {
    [$tx, $rx] = (new Channel())->split();
    $tx->send(123);
    expect($rx->recv())->toBe(123);
});

it('select() picks ready channel', function (): void {
    $c1 = new Channel();
    $c2 = new Channel();
    $c1->send('X');
    $idx = Channel::select([$c1, $c2], 100);
    expect($idx)->toBe(0);
});

it('throws after close', function (): void {
    $c = new Channel();
    $c->close();
    expect(fn () => $c->recv())->toThrow(RuntimeException::class);
});
