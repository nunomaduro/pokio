<?php

declare(strict_types=1);

namespace Pokio\Channel;

use RuntimeException;

use function is_resource;

/**
 * Unbuffered, blocking channel implemented with a UNIX socket-pair.
 */
final class Channel
{
    /** @var resource|null */
    private mixed $rx;

    /** @var resource|null */
    private mixed $tx;

    public function __construct()
    {
        $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, 0);
        if ($pair === false) {
            throw new RuntimeException('Cannot create channel, failed to open stream');
        }

        /** @var array{0:resource,1:resource} $pair */
        [$this->rx, $this->tx] = $pair;
    }

    public function __destruct()
    {
        $this->close();
    }

    /* ------------------------------------------------------------------ *
     *  Static select()
     * ------------------------------------------------------------------ */

    /**
     * Wait until at least one channel in $readable becomes ready.
     *
     * @param  list<self|ReadableChannel>  $readable
     * @return int|null index of ready channel, or null on timeout
     */
    public static function select(array $readable, ?int $timeoutMs = null): ?int
    {
        /** @var list<resource> $streams */
        $streams = [];
        foreach ($readable as $ch) {
            $stream = $ch instanceof self ? $ch->rx : $ch->stream();
            if (! is_resource($stream)) {
                throw new RuntimeException('closed channel passed to select()');
            }
            $streams[] = $stream;
        }

        $write = $except = null;
        $sec = $timeoutMs === null ? null : intdiv($timeoutMs, 1000);
        $usec = $timeoutMs === null ? null : ($timeoutMs % 1000) * 1000;

        $ready = stream_select($streams, $write, $except, $sec, $usec);

        if ($ready === false) {
            throw new RuntimeException('stream_select() failed');
        }
        if ($ready === 0) {
            return null;
        }

        /** @var int|false $idx */
        $idx = array_search($streams[0], array_map(
            static fn (self|ReadableChannel $c) => $c instanceof self ? $c->rx : $c->stream(),
            $readable
        ), true);

        return $idx === false ? null : $idx;
    }

    /* ------------------------------------------------------------------ *
     *  Send / Receive
     * ------------------------------------------------------------------ */

    public function send(mixed $msg): void
    {
        $this->assertOpen($this->tx);
        /** @var resource $tx */ $tx = $this->tx;

        $blob = serialize($msg);
        $len = pack('N', mb_strlen($blob));

        fwrite($tx, $len.$blob);
    }

    public function recv(): mixed
    {
        $this->assertOpen($this->rx);
        /** @var resource $rx */ $rx = $this->rx;

        $hdr = fread($rx, 4);
        if ($hdr === '' || $hdr === false) {
            throw new RuntimeException('channel closed');
        }

        $unpacked = unpack('Nlen', $hdr);
        if ($unpacked === false) {
            throw new RuntimeException('failed to unpack length header');
        }

        /** @var int $bytes */
        $bytes = $unpacked['len'];
        $blob = stream_get_contents($rx, $bytes);

        if ($blob === false) {
            throw new RuntimeException('failed to read channel payload');
        }

        /** @var mixed $value */
        $value = unserialize($blob);

        return $value;
    }

    /* ------------------------------------------------------------------ */

    public function close(): void
    {
        if (is_resource($this->rx)) {
            fclose($this->rx);
        }
        if (is_resource($this->tx)) {
            fclose($this->tx);
        }
        $this->rx = $this->tx = null;
    }

    /**
     * Return write-only and read-only “views” of the channel.
     *
     * @return array{WritableChannel, ReadableChannel}
     */
    public function split(): array
    {
        $this->assertOpen($this->tx);
        $this->assertOpen($this->rx);

        /** @var resource $tx */
        $tx = $this->tx;
        /** @var resource $rx */
        $rx = $this->rx;

        return [new WritableChannel($tx), new ReadableChannel($rx)];
    }

    /* ------------------------------------------------------------------ */
    private function assertOpen(mixed $handle): void
    {
        if (! is_resource($handle)) {
            throw new RuntimeException('channel closed');
        }
    }
}
