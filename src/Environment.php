<?php

declare(strict_types=1);

namespace Pokio;

use RuntimeException;

/**
 * @internal
 */
final class Environment
{
    /**
     * Cached value for maxProcesses to avoid redundant computations.
     */
    private static ?int $maxProcesses = null;

    /**
     * The number of processes that can be run in parallel.
     */
    public static function maxProcesses(): int
    {
        if (self::$maxProcesses !== null) {
            return self::$maxProcesses;
        }

        $cpuCores = (int) shell_exec('getconf _NPROCESSORS_ONLN');

        // @codeCoverageIgnoreStart
        if ($cpuCores <= 0) {
            $cpuCores = 1;
        }
        // @codeCoverageIgnoreEnd

        $ioFactor = (int) getenv('FORK_IO_FACTOR') ?: 3;
        $maxByCpu = $cpuCores * $ioFactor;

        $totalMemory = self::getTotalMemory(PHP_OS_FAMILY);

        $perProcessMemory = (int) getenv('FORK_MEM_PER_PROC') ?: 100 * 1024 * 1024; // 100MB
        $maxByMemory = intdiv($totalMemory, $perProcessMemory);

        $maxProcesses = min($maxByCpu, $maxByMemory);

        self::$maxProcesses = max(1, $maxProcesses);

        return self::$maxProcesses;
    }

    /**
     * Whether the current environment supports forking.
     */
    public static function supportsFork(): bool
    {
        return extension_loaded('pcntl') && extension_loaded('posix');
    }

    /**
     * Get the total memory of the system in bytes.
     *
     * @param  string  $os  (default: PHP_OS_FAMILY) The operating system family.
     * @param  string|null  $memInfo  (default: null) The memory information string (used for testing on linux).
     */
    private static function getTotalMemory(string $os = PHP_OS_FAMILY, ?string $memInfo = null): int
    {
        if ($os === 'Linux') {
            $memInfo = $memInfo ?? @file_get_contents('/proc/meminfo');

            if (! $memInfo || ! preg_match('/MemTotal:\s+(\d+) kB/', $memInfo, $matches)) {
                throw new RuntimeException('Unable to determine total memory on Linux');
            }

            return (int) $matches[1] * 1024;
        }

        if ($os === 'Darwin') {
            return (int) shell_exec('sysctl -n hw.memsize');
        }

        throw new RuntimeException("Unsupported OS: $os");
    }
}
