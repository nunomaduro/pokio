<?php

declare(strict_types=1);

namespace Pokio\Runtime\Fork;

use Closure;
use Pokio\Contracts\Future;
use RuntimeException;

/**
 * Represents the result of a forked process.
 *
 * @template TResult
 *
 * @implements Future<TResult>
 *
 * @internal
 */
final class ForkFuture implements Future
{
    /**
     * The result of the forked process, if any.
     *
     * @var TResult|null
     */
    private mixed $result = null;

    /**
     * Indicates whether the result has been resolved.
     */
    private bool $resolved = false;

    /**
     * Creates a new fork result instance.
     */
    public function __construct(
        private readonly int $pid,
        private readonly IPC $memory,
        private readonly Closure $onWait,
    ) {
        //
    }

    /**
     * Awaits the result of the future.
     *
     * @return TResult|null
     */
    public function await(): mixed
    {
        if ($this->resolved) {
            return $this->result;
        }

        pcntl_waitpid($this->pid, $status);

        // Check if the IPC file exists and is non-empty
        if (! file_exists($this->memory->path()) || filesize($this->memory->path()) === 0) {
            $this->resolved = true;

            return $this->result = null;
        }

        $this->onWait->__invoke($this->pid);

        $this->resolved = true;

        return $this->result = $this->unserializeResult($this->memory->pop());
    }

    /**
     * Safely unserializes a value from the forked process.
     *
     * @return TResult
     *
     * @throws RuntimeException
     */
    private function unserializeResult(string $data): mixed
    {
        $result = @unserialize($data);

        if ($result === false && $data !== 'b:0;') {
            throw new RuntimeException('Failed to unserialize fork result.');
        }

        return $result;
    }
}
