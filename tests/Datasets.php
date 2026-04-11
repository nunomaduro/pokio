<?php

declare(strict_types=1);

use Pokio\Environment;

dataset('runtimes', [
    'sync' => fn () => pokio()->useSync(),
    'fork' => function (): void {
        if (! Environment::supportsFork()) {
            $this->markTestSkipped('Fork is not supported in this environment.');
        }

        pokio()->useFork();
    },
]);
