<?php

declare(strict_types=1);

namespace Pokio;

use Pokio\Contracts\Future;

/**
 * @internal
 */
final class UnwaitedFutureManager
{
    /**
     * Holds the singleton instance of the unwaited future manager.
     */
    private static ?self $instance = null;

    /**
     * Creates a new future manager instance.
     *
     * @param  array<string, Future<mixed>>  $queue
     */
    private function __construct(
        private array $queue,
    ) {
        //
    }

    /**
     * Called when the future manager is destructed.
     */
    public function __destruct()
    {
        $this->run();
    }

    /**
     * Fetches the singleton instance of the future manager.
     */
    public static function instance(): self
    {
        return self::$instance ??= new self(
            [],
        );
    }

    /**
     * Flushes the execution queue, removing all scheduled futures.
     */
    public function flush(): void
    {
        $this->queue = [];
    }

    /**
     * Schedules a future for execution.
     *
     * @param  Future<mixed>  $future
     */
    public function schedule(Future $future): void
    {
        $hash = spl_object_hash($future);

        $this->queue[$hash] = $future;
    }

    /**
     * Unschedules a future, removing it from the execution queue.
     *
     * @param  Future<mixed>  $future
     */
    public function unschedule(Future $future): void
    {
        $hash = spl_object_hash($future);

        if (isset($this->queue[$hash])) {
            unset($this->queue[$hash]);
        }
    }

    /**
     * Runs all scheduled futures, resolving them in the order they were added.
     */
    public function run(): void
    {
        $queue = $this->queue;

        foreach ($queue as $future) {
            $future->await();

            $this->unschedule($future);
        }

        if (count($this->queue) > 0) {
            $this->run();
        }
    }
}
