<?php

declare(strict_types=1);

use Pokio\Environment;

test('fork runtime waits if there are too many processes', function (): void {
    $maxProcesses = Environment::maxProcesses();
    $numberOfProcesses = $maxProcesses + 1;

    $promises = [];

    for ($i = 0; $i < $numberOfProcesses; $i++) {
        $promises[] = async(function () {
            return 1;
        });
    }

    $results = await($promises);
    expect($results)->toHaveCount($numberOfProcesses);
})->with('runtimes')->skip(fn () => ! Environment::supportsFork(), 'Fork runtime is not supported on this environment.');
