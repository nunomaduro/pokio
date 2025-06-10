<?php

declare(strict_types=1);

namespace Pokio;

use LogicException;
use Pokio\Contracts\Runtime;
use Pokio\Runtime\Fork\ForkRuntime;
use Pokio\Runtime\Sync\SyncRuntime;

/**
 * @internal
 */
final class Kernel
{
    /**
     * The kernel's singleton.
     */
    private static ?self $instance = null;

    /**
     * The sync runtime instance.
     */
    private Runtime $syncRuntime;

    /**
     * The async runtime instance.
     */
    private Runtime $asyncRuntime;

    /**
     * The process ID of the orchestrator.
     */
    private function __construct(
        private readonly int $orchestratorPid,
    ) {
        //
    }

    /**
     * The process ID of the orchestrator.
     */
    public static function instance(): self
    {
        return self::$instance ??= new self((int) getmypid());
    }

    /**
     * Specifies that pokio should use the fork as the async runtime.
     */
    public function useFork(?int $maxProcesses = null): void
    {
        if (Environment::supportsFork() === false) {
            throw new LogicException('Fork is not supported on this environment.');
        }

        $this->asyncRuntime = new ForkRuntime($maxProcesses ?? Environment::maxProcesses());
    }

    /**
     * Specifies that pokio should use the sync runtime.
     */
    public function useSync(): void
    {
        $this->asyncRuntime = $this->syncRuntime ??= new SyncRuntime();
    }

    /**
     * Resolves pokio's current runtime.
     */
    public function runtime(): Runtime
    {
        if (self::instance()->isOrchestrator() === false) {
            return $this->syncRuntime ??= new SyncRuntime();
        }

        return $this->asyncRuntime ??= (function () {
            if (Environment::supportsFork()) {
                return new ForkRuntime(Environment::maxProcesses());
            }

            return $this->syncRuntime ??= new SyncRuntime();
        })();
    }

    /**
     * Whether the current process is the orchestrator.
     */
    public function isOrchestrator(): bool
    {
        return $this->orchestratorPid === getmypid();
    }
}
