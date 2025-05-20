<?php

declare(strict_types=1);

use Pokio\Promise;

if (! function_exists('async')) {
    /**
     * Runs a callback asynchronously and returns a promise.
     */
    function async(Closure $callback): Promise
    {
        $promise = new Promise($callback);

        $promise->run();

        return $promise;
    }
}

if (! function_exists('await')) {
    /**
     * Awaits the resolution of a promise.
     *
     * @param  array<int, Promise>|Promise  $promises
     */
    function await(array|Promise $promises): mixed
    {
        if (! is_array($promises)) {
            return $promises->resolve();
        }

        return array_map(
            static fn (Promise $promise): mixed => $promise->resolve(),
            $promises
        );
    }
}
