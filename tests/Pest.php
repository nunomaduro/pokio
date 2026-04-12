<?php

declare(strict_types=1);

use Pokio\Environment;

pest()->beforeEach(function (): void {
    match ($_ENV['POKIO_RUNTIME'] ?? null) {
        'sync' => Environment::useSync(),
        'fork' => Environment::useFork(),
        default => null,
    };
});

if (! function_exists('ensureForkEnvironment')) {
    /**
     * Ensures the current environment is set to fork.
     * Skips the test if the environment does not support forking.
     */
    function ensureForkEnvironment(): void
    {
        if (! Environment::supportsFork()) {
            test()->markTestSkipped('Fork is not supported in this environment.');
        }

        pokio()->useFork();
    }
}

if (! function_exists('ensureSyncEnvironment')) {
    /**
     * Ensures the current environment is set to sync.
     */
    function ensureSyncEnvironment(): void
    {
        pokio()->useSync();
    }
}
