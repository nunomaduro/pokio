<?php

declare(strict_types=1);

namespace Pokio\Channel;

use RuntimeException;

use function is_resource;

final class ReadableChannel
{
    /** @param resource $rx */
    public function __construct(private mixed $rx) {}

    public function recv(): mixed
    {
        if (! is_resource($this->rx)) {
            throw new RuntimeException('channel closed');
        }

        /** @var resource $rx */
        $rx = $this->rx;

        // ---- read 4-byte length header ------------------------------------
        $hdr = fread($rx, 4);
        if ($hdr === '' || $hdr === false) {
            throw new RuntimeException('channel closed');
        }

        $unpacked = unpack('Nlen', $hdr);
        if ($unpacked === false || ! isset($unpacked['len'])) {
            throw new RuntimeException('invalid length header');
        }

        /** @var int $bytes */
        $bytes = $unpacked['len'];

        // ---- read payload --------------------------------------------------
        $blob = stream_get_contents($rx, $bytes);
        if ($blob === false) {
            throw new RuntimeException('failed to read channel payload');
        }

        /** @var mixed $value */
        $value = unserialize($blob);

        return $value;
    }

    /** Expose raw stream for Channel::select(). @return resource */
    public function stream(): mixed
    {
        return $this->rx;
    }
}
