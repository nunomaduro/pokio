<?php

declare(strict_types=1);

namespace Pokio\Contracts;

use Closure;

interface Runtime
{
    /**
     * Defers the given callback to be executed asynchronously.
     */
    public function defer(Closure $callback): Result;
}
