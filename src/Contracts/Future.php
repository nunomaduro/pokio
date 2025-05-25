<?php

declare(strict_types=1);

namespace Pokio\Contracts;

/**
 * @internal
 *
 * @template TResult
 */
interface Future
{
    /**
     * The result of the asynchronous operation.
     *
     * @return TResult
     */
    public function await(): mixed;

    /**
     * Cancels the asynchronous operation.
     *
     * @return bool Whether the operation was successfully cancelled
     */
    public function cancel(): bool;
}
