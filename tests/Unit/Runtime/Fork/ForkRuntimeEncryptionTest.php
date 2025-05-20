<?php

test('fork runtime with encryption works correctly', function (): void {
    // Only run this test when the fork runtime is active and the required extensions are available
    if (! extension_loaded('pcntl') || ! extension_loaded('posix')) {
        $this->markTestSkipped('pcntl and posix extensions are required for this test');
    }

    // Force the fork runtime
    \Pokio\Environment::useFork();

    // Test with a complex data structure to ensure serialization works correctly
    $complexData = [
        'string' => 'Hello, world!',
        'number' => 42,
        'boolean' => true,
        'array' => [1, 2, 3],
        'object' => (object) ['foo' => 'bar'],
    ];

    $promise = async(fn (): array => $complexData);
    $result = await($promise);

    expect($result)->toBe($complexData);
});
