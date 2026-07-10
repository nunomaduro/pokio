<?php

declare(strict_types=1);

use function PHPStan\Testing\assertType;

$promise = async(fn () => 1);
assertType('int', await($promise));

$promiseA = async(fn () => 'string');
$promiseB = async(fn () => 'string');
assertType('array<int, string>', await([$promiseA, $promiseB]));

$promise = async(fn () => 1)
    ->then(fn (int $result) => $result * 2);

assertType('int', await($promise));

$promise = async(fn () => 1)
    ->then(fn (int $result) => 'string');

assertType('string', await($promise));

$promise = async(fn () => throw new Exception())
    ->catch(fn (Throwable $th) => 1);

assertType('int', await($promise));

$promise = async(fn () => throw new Exception())
    ->catch(fn (Throwable $th) => throw new Exception());

assertType('never', await($promise));
