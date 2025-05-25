<?php

declare(strict_types=1);

namespace Pokio;

use Closure;
use Pokio\Contracts\Future;
use Pokio\Support\Reflection;
use Throwable;

/**
 * @template TReturn
 *
 * @internal
 */
final class Promise
{
    /**
     * The result of the asynchronous operation.
     *
     * @var Future<TReturn>
     */
    private Future $future;

    /**
     * Whether the promise has been cancelled.
     */
    private bool $cancelled = false;

    /**
     * Creates a new promise instance.
     *
     * @param  Closure(): TReturn  $callback
     */
    public function __construct(private readonly Closure $callback)
    {
        //
    }

    /**
     * Defer the given callback to be executed asynchronously.
     */
    public function defer(): void
    {
        if ($this->cancelled) {
            return;
        }

        $this->future ??= Kernel::instance()->runtime()->defer($this->callback);
    }

    /**
     * Resolves the promise.
     *
     * @return TReturn
     */
    public function resolve(): mixed
    {
        if ($this->cancelled) {
            throw new \RuntimeException('Cannot resolve a cancelled promise');
        }

        $this->defer();

        return $this->future->await();
    }

    /**
     * Cancels the promise.
     *
     * @return bool Whether the promise was successfully cancelled
     */
    public function cancel(): bool
    {
        if ($this->cancelled) {
            return false;
        }

        $this->cancelled = true;

        if (isset($this->future)) {
            return $this->future->cancel();
        }

        return true;
    }

    /**
     * Adds a then callback to the promise.
     *
     * @template TThenReturn
     *
     * @param  Closure(TReturn): TThenReturn  $then
     * @return self<TThenReturn>
     */
    public function then(Closure $then): self
    {
        $callback = $this->callback;

        // @phpstan-ignore-next-line
        return new self(function () use ($callback, $then) {
            $result = $callback();

            if ($result instanceof Promise) {
                // @phpstan-ignore-next-line
                return $result->then($then);
            }

            return $then($result);
        });
    }

    /**
     * Adds a catch callback to the promise.
     *
     * @template TCatchReturn
     *
     * @param  Closure(Throwable): TCatchReturn  $catch
     * @return self<TReturn|TCatchReturn>
     */
    public function catch(Closure $catch): self
    {
        $callback = $this->callback;

        return new self(function () use ($callback, $catch) {
            try {
                return $callback();
            } catch (Throwable $throwable) {
                if (! Reflection::isCatchable($catch, $throwable)) {
                    throw $throwable;
                }

                return ($catch)($throwable);
            }
        });
    }

    /**
     * Adds a finally callback to the promise.
     *
     * @param  Closure(): void  $finally
     * @return self<TReturn>
     */
    public function finally(Closure $finally): self
    {
        $callback = $this->callback;

        return new self(function () use ($callback, $finally) {
            try {
                return $callback();
            } finally {
                ($finally)();
            }
        });
    }
}
