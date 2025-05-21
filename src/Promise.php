<?php

declare(strict_types=1);

namespace Pokio;

use Closure;
use Pokio\Contracts\Result;
use Throwable;

/**
 * @template TReturn
 */
final class Promise
{
    private Result $result;

    private Closure $catch;

    private Closure $then;

    private Closure $finally;

    /**
     * Creates a new promise instance.
     *
     * @param  Closure(): TReturn  $callback
     */
    public function __construct(private readonly Closure $callback)
    {
        $this->catch = fn (): null => null;
        $this->then = fn (): null => null;
        $this->finally = fn (): null => null;
    }

    public function run(): void
    {
        $runtime = Environment::runtime();

        $this->result = $runtime->defer($this->callback);
    }

    public function catch(Closure $callback): self
    {
        $this->catch = $callback;

        return $this;
    }

    public function then(Closure $callback): self
    {
        $this->then = $callback;

        return $this;
    }

    public function finally(Closure $callback): self
    {
        $this->finally = $callback;

        return $this;
    }

    /**
     * Resolves the promise.
     *
     * @return TReturn
     */
    public function resolve(): mixed
    {
        try {
            $result = $this->result->get();

            if ($thenResult = ($this->then)($result)) {
                return $thenResult;
            }

            return $result;
        } catch (Throwable $exception) {
            if ($result = ($this->catch)($exception)) {
                return $result;
            }

            throw $exception;
        } finally {
            if ($result = ($this->finally)()) {
                return $result;
            }
        }
    }
}
