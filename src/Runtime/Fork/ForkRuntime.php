<?php

declare(strict_types=1);

namespace Pokio\Runtime\Fork;

use Closure;
use Pokio\Contracts\Future;
use Pokio\Contracts\Runtime;
use Pokio\Kernel;
use Pokio\Promise;
use Pokio\UnwaitedFutureManager;
use RuntimeException;
use Throwable;

/**
 * @internal
 */
final class ForkRuntime implements Runtime
{
    /**
     * The PIDs of the currently running processes.
     *
     * @var array<int, true>
     */
    private static array $processes = [];

    /**
     * Creates a new fork runtime instance.
     */
    public function __construct(private int $maxProcesses)
    {
        //
    }

    /**
     * Cleans up any remaining processes on destruction, if any.
     */
    public function __destruct()
    {
        if (Kernel::instance()->isOrchestrator()) {
            foreach (array_keys(self::$processes) as $pid) {
                pcntl_waitpid($pid, $status);
            }
        }
    }

    /**
     * Defers the given callback to be executed asynchronously.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return Future<TResult>
     */
    public function defer(Closure $callback): Future
    {
        while (count(self::$processes) >= $this->maxProcesses) {
            $this->waitForProcess();

            // Adding a little sleep to prevent busy-waiting and CPU
            // starvation, allowing other processes to run in the
            // meantime. -------------------------------------

            usleep(1000);
        }

        $ipc = IPC::create();
        $pid = pcntl_fork();

        if ($pid === -1) {
            throw new RuntimeException('Failed to fork process');
        }

        if ($pid === 0) {
            UnwaitedFutureManager::instance()->flush();

            // @codeCoverageIgnoreStart
            try {
                $result = $callback();

                if ($result instanceof Promise) {
                    $result = await($result);
                }
            } catch (Throwable $exception) {
                $result = new ThrowableCapsule($exception);
            }

            $data = serialize($result);

            $ipc->put($data);

            exit(0);
            // @codeCoverageIgnoreEnd
        }

        self::$processes[$pid] = true;

        /** @var Future<TResult> $future */
        // @phpstan-ignore-next-line
        $future = new ForkFuture($pid, $ipc, function (int $pid): void {
            unset(self::$processes[$pid]);
        });

        return $future;
    }

    /**
     * Waits for a process to finish and removes it from the list of processes.
     */
    private function waitForProcess(): void
    {
        $pid = pcntl_wait($status, WNOHANG);

        if ($pid > 0) {
            unset(self::$processes[$pid]);
        }
    }
}
