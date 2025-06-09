<?php

declare(strict_types=1);

use Pokio\Kernel;
use Pokio\Pokio;
use Pokio\Promise;

if (! function_exists('async')) {
    /**
     * Runs a callback asynchronously and returns a promise.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return Promise<TReturn>
     */
    function async(Closure $callback): Promise
    {
        return new Promise($callback);
    }
}
if (! function_exists('await')) {
    /**
     * Awaits the resolution of a promise.
     *
     * @template TReturn
     *
     * @param  array<int, Promise<TReturn>>|Promise<TReturn>  $promises
     * @return ($promises is array ? array<int, TReturn> : TReturn)
     */
    function await(array|Promise $promises): mixed
    {
        if (! is_array($promises)) {
            return $promises->resolve();
        }

        foreach ($promises as $promise) {
            $promise->defer();
        }

        return array_map(
            static fn (Promise $promise): mixed => $promise->resolve(),
            $promises
        );
    }
}

if (! function_exists('pokio')) {
    /**
     * Returns the Pokio kernel instance.
     */
    function pokio(): Pokio
    {
        return new Pokio;
    }
}
