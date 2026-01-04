<?php

declare(strict_types=1);

use Pokio\Environment;
use Pokio\Runtime\Fork\ForkFuture;
use Pokio\Runtime\Fork\IPC;

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

test('unserializeResult returns correct value for valid data', function (): void {
    // Mock IPC and other dependencies with dummy values
    $pid = 1234;
    $ipc = IPC::create();
    $onWait = fn () => null;

    $future = new ForkFuture($pid, $ipc, $onWait);

    // Use reflection to access private unserializeResult method
    $reflection = new ReflectionClass($future);
    $method = $reflection->getMethod('unserializeResult');
    $method->setAccessible(true);

    $serializedTrue = serialize('test data');
    $result = $method->invoke($future, $serializedTrue);

    expect($result)->toBe('test data');
});

test('unserializeResult throws exception for invalid data', function (): void {
    $pid = 1234;
    $ipc = IPC::create();
    $onWait = fn () => null;

    $future = new ForkFuture($pid, $ipc, $onWait);

    $reflection = new ReflectionClass($future);
    $method = $reflection->getMethod('unserializeResult');
    $method->setAccessible(true);

    $invalidSerialized = 'invalid serialized string';

    $method->invoke($future, $invalidSerialized);
})->throws(RuntimeException::class, 'Failed to unserialize fork result.');
