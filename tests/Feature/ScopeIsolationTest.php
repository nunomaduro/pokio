<?php

declare(strict_types=1);

test('sync: global variables do leak into closure scope', function (): void {
    ensureSyncEnvironment();

    global $leakyGlobal;
    $leakyGlobal = 'should not be visible';

    $promise = async(fn () => isset($GLOBALS['leakyGlobal']));
    expect(await($promise))->toBeTrue();
});

test('sync: closure variables using `use` do leak outside', function (): void {
    ensureSyncEnvironment();

    $value = 'normal';
    $result = await(async(function () use (&$value) {
        $value = 'secret';

        return $value;
    }));
    expect($result)->toBe('secret')
        ->and($value)->toBe('secret');
});

test('sync: constants are available inside closures', function (): void {
    ensureSyncEnvironment();

    define('SYNC_SCOPE_TEST_CONST', 'yes');
    $result = await(async(fn () => constant('SYNC_SCOPE_TEST_CONST')));

    expect($result)->toBe('yes');
});

test('sync: external variables are visible without `use`', function (): void {
    ensureSyncEnvironment();

    $secret = 'nope';

    $promise = async(fn () => isset($secret));
    expect(await($promise))->toBeTrue();
});

test('sync: callback does not persist GLOBALS in sync env', function (): void {
    ensureSyncEnvironment();

    $GLOBALS['test'] = 1;

    $promise = async(function () {
        $GLOBALS['test'] = 2;

        return 1;
    });

    $result = await($promise);

    expect($result)->toBe(1)
        ->and($GLOBALS['test'])->toBe(2);
});

test('fork: global variables do leak into closure scope', function (): void {
    ensureForkEnvironment();

    global $leakyGlobal;
    $leakyGlobal = 'should not be visible';

    $promise = async(fn () => isset($GLOBALS['leakyGlobal']));
    expect(await($promise))->toBeTrue();
});

test('fork: closure variables using `use` do not leak outside', function (): void {
    ensureForkEnvironment();

    $value = 'normal';
    $result = await(async(function () use (&$value) {
        $value = 'secret';

        return $value;
    }));

    expect($result)->toBe('secret')
        ->and($value)->toBe('normal');
});

test('fork: constants are available inside closures', function (): void {
    ensureForkEnvironment();

    define('FORK_SCOPE_TEST_CONST', 'yes');
    $result = await(async(fn () => constant('FORK_SCOPE_TEST_CONST')));

    expect($result)->toBe('yes');
});

test('fork: external variables are visible without `use`', function (): void {
    ensureForkEnvironment();

    $secret = 'nope';

    $promise = async(fn () => isset($secret));
    expect(await($promise))->toBeTrue();
});

test('fork: callback does not persist GLOBALS in fork env', function (): void {
    ensureForkEnvironment();

    $GLOBALS['test'] = 1;

    $promise = async(function () {
        $GLOBALS['test'] = 2;

        return 1;
    });

    $result = await($promise);

    expect($result)->toBe(1)
        ->and($GLOBALS['test'])->toBe(1);
});
