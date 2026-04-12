<?php

declare(strict_types=1);

use Pokio\Environment;

test('environment get total memory for linux with meminfo', function (): void {
    $reflection = new ReflectionClass(Environment::class);

    $reflectionMethod = $reflection->getMethod('getTotalMemory');

    $totalMemory = $reflectionMethod->invokeArgs(null, ['Linux', 'MemTotal: 123456 kB']);
    expect($totalMemory)->toBe(123456 * 1024);
});

test('environment get total memory for linux without meminfo', function (): void {
    $reflection = new ReflectionClass(Environment::class);

    $reflectionMethod = $reflection->getMethod('getTotalMemory');

    expect(fn () => $reflectionMethod->invokeArgs(null, ['Linux', '']))
        ->toThrow(RuntimeException::class, 'Unable to determine total memory on Linux');
})->skip(fn () => PHP_OS_FAMILY === 'Linux', 'For Linux this actually falls back to /proc/meminfo');

test('environment get total memory for darwin', function (): void {
    $reflection = new ReflectionClass(Environment::class);

    $reflectionMethod = $reflection->getMethod('getTotalMemory');

    $totalMemory = $reflectionMethod->invokeArgs(null, ['Darwin']);
    expect($totalMemory)->toBeGreaterThan(0);
})->skip(fn () => PHP_OS_FAMILY !== 'Darwin', 'This test is only valid for Darwin OS');

test('environment get total memory for unsupported os', function (): void {
    $reflection = new ReflectionClass(Environment::class);

    $reflectionMethod = $reflection->getMethod('getTotalMemory');

    expect(fn () => $reflectionMethod->invokeArgs(null, ['UnsupportedOS']))
        ->toThrow(RuntimeException::class, 'Unsupported OS: UnsupportedOS');
});

test('hasXdebugDebugMode returns true when xdebug debug mode is enabled', function (): void {
    $reflection = new ReflectionClass(Environment::class);
    $method = $reflection->getMethod('hasXdebugDebugMode');

    $result = $method->invoke(null);

    $mode = getenv('XDEBUG_MODE');

    if ($mode === false) {
        $mode = (string) ini_get('xdebug.mode');
    }

    $expectedActive = str_contains($mode, 'debug');

    expect($result)->toBe($expectedActive);
})->skip(fn () => ! extension_loaded('xdebug'), 'Xdebug must be loaded');

test('supportsFork returns false when xdebug debug mode is enabled', function (): void {
    expect(Environment::supportsFork())->toBeFalse();
})->skip(function (): bool {
    if (! extension_loaded('xdebug')) {
        return true;
    }

    $mode = getenv('XDEBUG_MODE');

    if ($mode === false) {
        $mode = (string) ini_get('xdebug.mode');
    }

    return ! str_contains($mode, 'debug');
});
