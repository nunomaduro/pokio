<?php

declare(strict_types=1);

namespace Pokio;

use Closure;
use Pokio\Contracts\Result;
use RuntimeException;
use Throwable;

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

    /**
     * Waits for all promises to resolve and returns their results as an array.
     * If any promise rejects, the first rejection is thrown.
     *
     * @template TReturn
     *
     * @param  array<int, Promise<TReturn>>  $promises
     * @return Promise<array<int, TReturn>>
     */
    public static function all(array $promises): self
    {
        return async(function () use ($promises) {
            $results = [];
            foreach ($promises as $promise) {
                $results[] = $promise->resolve();
            }

            return $results;
        });
    }

    /**
     * Resolves as soon as any promise resolves, or throws if all reject.
     *
     * @template TReturn
     *
     * @param  array<int, Promise<TReturn>>  $promises
     * @return Promise<TReturn>
     */
    public static function any(array $promises): self
    {
        return async(function () use ($promises) {
            $errors = [];
            foreach ($promises as $promise) {
                try {
                    return $promise->resolve();
                } catch (Throwable $e) {
                    $errors[] = $e;
                }
            }
            throw new RuntimeException('All promises rejected', 0, $errors[0] ?? null);
        });
    }

    /**
     * Resolves or rejects as soon as one of the promises resolves or rejects.
     *
     * @template TReturn
     *
     * @param  array<int, Promise<TReturn>>  $promises
     * @return Promise<TReturn>
     */
    public static function race(array $promises): self
    {
        return async(function () use ($promises) {
            $resolved = false;
            $result = null;
            $exception = null;
            foreach ($promises as $promise) {
                try {
                    $result = $promise->resolve();
                    $resolved = true;
                    break;
                } catch (Throwable $e) {
                    $exception = $e;
                    $resolved = true;
                    break;
                }
            }
            if ($resolved && ! $exception instanceof Throwable) {
                return $result;
            }
            if ($resolved && $exception instanceof Throwable) {
                throw $exception;
            }
            throw new RuntimeException('No promises to race');
        });
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
}
