<?php

use Pokio\Support\JoinHandle;

test('await returns the resolved result', function () {
    $handle = new JoinHandle(async(fn() => 42));

    expect($handle->await())->toBe(42);
});

test('promise method returns the raw promise', function () {
    $promise = async(fn() => 123);
    $handle = new JoinHandle($promise);

    expect($handle->promise())->toBe($promise);
});
