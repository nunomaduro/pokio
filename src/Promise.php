<?php

declare(strict_types=1);

namespace Pokio;

use Closure;
use Pokio\Contracts\Future;
use Pokio\Exceptions\PromiseAlreadyStarted;
use Pokio\Runtime\Sync\SyncFuture;
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
     * The process ID when the promise was created.
     */
    private readonly string $pid;

    /**
     * The result of the asynchronous operation.
     *
     * @var Future<TReturn>|null
     */
    private ?Future $future = null;

    /**
     * Creates a new promise instance.
     *
     * @param  Closure(): TReturn  $callback
     */
    public function __construct(
        private readonly Closure $callback
    ) {
        $this->pid = (string) getmypid();
    }

    /**
     * Resolves the promise when the object is destroyed.
     */
    public function __destruct()
    {
        if ((string) getmypid() !== $this->pid) {
            return;
        }

        $this->defer();

        assert($this->future instanceof Future);

        if ($this->future->awaited()) {
            return;
        }

        UnwaitedFutureManager::instance()->schedule($this->future);
    }

    /**
     * Invokes the promise, defering the callback to be executed immediately.
     *
     * @return TReturn
     */
    public function __invoke(): mixed
    {
        return $this->resolve();
    }

    /**
     * Defer the given callback to be executed asynchronously.
     */
    public function defer(): void
    {
        $this->future ??= Kernel::instance()->runtime()->defer($this->callback);
    }

    /**
     * Resolves the promise.
     *
     * @return TReturn
     */
    public function resolve(): mixed
    {
        $this->defer();

        assert($this->future instanceof Future);

        return $this->future->await();
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
        $this->ignore();

        $callback = $this->callback;

        // @phpstan-ignore-next-line
        return new self(function () use ($callback, $then) {
            $result = $callback();

            if ($result instanceof Promise) {
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
        $this->ignore();

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
        $this->ignore();

        $callback = $this->callback;

        return new self(function () use ($callback, $finally) {
            try {
                return $callback();
            } finally {
                ($finally)();
            }
        });
    }

    /**
     * Ignores the promise, effectively discarding the result.
     */
    private function ignore(): void
    {
        if ($this->future instanceof Future) {
            throw new PromiseAlreadyStarted();
        }

        // @phpstan-ignore-next-line
        $this->future = new SyncFuture(static fn () => null);
    }
}
