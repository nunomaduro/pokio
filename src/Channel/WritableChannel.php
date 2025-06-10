<?php

declare(strict_types=1);

namespace Pokio\Channel;

use RuntimeException;

use function is_resource;

final class WritableChannel
{
    /** @param resource $tx */
    public function __construct(private mixed $tx) {}

    public function send(mixed $msg): void
    {
        if (! is_resource($this->tx)) {
            throw new RuntimeException('channel closed');
        }
        $blob = serialize($msg);
        $len = pack('N', mb_strlen($blob));
        fwrite($this->tx, $len.$blob);
    }
}
