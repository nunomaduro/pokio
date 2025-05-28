<?php

declare(strict_types=1);

namespace Pokio\Runtime\Sync;

use Closure;
use Pokio\Contracts\Future;
use Pokio\Promise;

/**
 * @internal
 *
 * @template TResult
 *
 * @implements Future<TResult>
 */
final class SyncFuture implements Future
{
    /**
     * Whether the future has been cancelled.
     */
    private bool $cancelled = false;

    /**
     * Creates a new sync result instance.
     *
     * @param  Closure(): TResult  $callback
     */
    public function __construct(private readonly Closure $callback)
    {
        //
    }

    /**
     * Awaits the result of the future.
     *
     * @return TResult
     */
    public function await(): mixed
    {
        if ($this->cancelled) {
            throw new \RuntimeException('Cannot await a cancelled future');
        }

        $result = ($this->callback)();

        if ($result instanceof Promise) {
            return await($result);
        }

        return $result;
    }

    /**
     * Cancels the future.
     *
     * @return bool Whether the future was successfully cancelled
     */
    public function cancel(): bool
    {
        if ($this->cancelled) {
            return false;
        }

        $this->cancelled = true;
        return true;
    }
}
