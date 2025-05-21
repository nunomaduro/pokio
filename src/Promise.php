<?php

declare(strict_types=1);

namespace Pokio;

use Closure;
use Pokio\Contracts\Result;

/**
 * @template TReturn
 */
final class Promise
{
    private Result $result;

    /**
     * Creates a new promise instance.
     *
     * @param  Closure(): TReturn  $callback
     */
    public function __construct(private readonly Closure $callback, private readonly ?Closure $rescue = null)
    {
        //
    }

    public function run(): void
    {
        $runtime = Environment::runtime();

        $this->result = $runtime->defer($this->callback, $this->rescue);
    }

    /**
     * Resolves the promise.
     *
     * @return TReturn
     */
    public function resolve(): mixed
    {
        return $this->result->get();
    }

    /**
     * Chains a callback to be executed after the promise resolves.
     *
     * @template TNextReturn
     *
     * @param  Closure(TReturn): TNextReturn  $onFulfilled
     * @return Promise<TNextReturn>
     */
    public function then(Closure $onFulfilled): self
    {
        return async(function () use ($onFulfilled) {
            $result = await($this);
            return $onFulfilled($result);
        });
    }
}
