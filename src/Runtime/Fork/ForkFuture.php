<?php

declare(strict_types=1);

namespace Pokio\Runtime\Fork;

use Closure;
use Pokio\Contracts\Future;

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
     * Indicates whether the future has been cancelled.
     */
    private bool $cancelled = false;

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
        if ($this->cancelled) {
            throw new \RuntimeException('Cannot await a cancelled future');
        }

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

        /** @var TResult $result */
        $result = unserialize($this->memory->pop());

        return $this->result = $result;
    }

    /**
     * Cancels the future by terminating the child process.
     *
     * @return bool Whether the future was successfully cancelled
     */
    public function cancel(): bool
    {
        if ($this->cancelled || $this->resolved) {
            return false;
        }

        $this->cancelled = true;

        // Send SIGTERM to the child process
        posix_kill($this->pid, SIGTERM);

        // Wait for the process to terminate
        pcntl_waitpid($this->pid, $status);

        // Clean up the IPC file
        if (file_exists($this->memory->path())) {
            unlink($this->memory->path());
        }

        return true;
    }
}
