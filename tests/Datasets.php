<?php

declare(strict_types=1);

use Pokio\Environment;

dataset('runtimes', [
    'sync' => fn () => Environment::useSync(),
    'fork' => function (): void {
        if (! extension_loaded('pcntl') || ! extension_loaded('posix')) {
            $this->markTestSkipped('The pcntl and posix extensions are required to use the fork runtime.');
        }

        Environment::useFork();
    },
]);
