<?php

declare(strict_types=1);

namespace Pokio\Support;

use Closure;

final class Pokio
{
    public static function spawn(Closure $callback): JoinHandle
    {
        return new JoinHandle(async($callback));
    }

    public static function join(array|JoinHandle $handles): mixed
    {
        if (! is_array($handles)) {
            return $handles->await();
        }

        return array_map(fn(JoinHandle $handle): mixed => $handle->await(), $handles);
    }
}
