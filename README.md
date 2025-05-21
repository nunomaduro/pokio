<a href="https://nunomaduro.com/">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="art/header-dark.png">
    <img alt="Logo for pokio" src="art/header-light.png">
  </picture>
</a>

# Pokio

<p>
    <a href="https://github.com/nunomaduro/pokio/actions"><img src="https://github.com/nunomaduro/pokio/actions/workflows/tests.yml/badge.svg" alt="Build Status"></a>
    <a href="https://packagist.org/packages/nunomaduro/pokio"><img src="https://img.shields.io/packagist/dt/nunomaduro/pokio" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/nunomaduro/pokio"><img src="https://img.shields.io/packagist/v/nunomaduro/pokio" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/nunomaduro/pokio"><img src="https://img.shields.io/packagist/l/nunomaduro/pokio" alt="License"></a>
</p>

**Pokio** is a dead simple **Asynchronous API for PHP** that just works! Here is an example:

```php
$promiseA = async(function () {
    sleep(2);

    return 'Task 1';
});

$promiseB = async(function () {
    sleep(2);

    return 'Task 2';
});

// just takes 2 seconds...
[$resA, $resB] = await([$promiseA, $promiseB]);

echo $resA; // Task 1
echo $resB; // Task 2
```

## Installation

> **Requires [PHP 8.3+](https://php.net/releases/)**.

> **Note:** This package is a **work in progress (don't use it yet)**.

⚡️ Get started by requiring the package using [Composer](https://getcomposer.org):

```bash
composer require nunomaduro/pokio:@dev
```

## Usage

-   `async`

The `async` global function returns a promise that will eventually resolve the value returned by the given closure.

```php
$promise = async(function () {
    return 1 + 1;
});

var_dump(await($promise)); // int(2)
```

Similar to other promise libraries, pokio allows you to chain methods to the promise. The `then` method will be called when the promise resolves.

```php
$promise = async(fn (): int => 1 + 2)
    ->then(fn ($result): int => $result + 2)
    ->then(fn ($result): int => $result * 2);

$result = await($promise);
var_dump($result); // int(8)
```

Optionally, you may chain a `catch` method to the promise, which will be called if the given closure throws an exception.

```php
$promise = async(function () {
    throw new Exception('Error');
})->catch(function (Throwable $e) {
    return 'Rescued: ' . $e->getMessage();
});

var_dump(await($promise)); // string(16) "Rescued: Error"
```

If you don't want to use the `catch` method, you can also use native `try/catch` block.

```php
$promise = async(function () {
    throw new Exception('Error');
});

try {
    await($promise);
} catch (Throwable $e) {
    var_dump('Rescued: ' . $e->getMessage()); // string(16) "Rescued: Error"
}
```

If you return a promise from the closure, it will be awaited automatically.

```php
$promise = async(function () {
    return async(function () {
        return 1 + 1;
    });
});

var_dump(await($promise)); // int(2)
```

-   `await`

The `await` global function will block the current process until the given promise resolves.

```php
$promise = async(function () {
    sleep(2);

    return 1 + 1;
});

var_dump(await($promise)); // int(2)
```

You may also pass an array of promises to the `await` function, which will be awaited simultaneously.

```php
$promiseA = async(function () {
    sleep(2);

    return 1 + 1;
});

$promiseB = async(function () {
    sleep(2);

    return 2 + 2;
});

var_dump(await([$promiseA, $promiseB])); // array(2) { [0]=> int(2) [1]=> int(4) }
```

## Follow Nuno

-   Follow the creator Nuno Maduro:
    -   YouTube: **[youtube.com/@nunomaduro](https://www.youtube.com/@nunomaduro)** — Videos every weekday
    -   Twitch: **[twitch.tv/enunomaduro](https://www.twitch.tv/enunomaduro)** — Streams (almost) every weekday
    -   Twitter / X: **[x.com/enunomaduro](https://x.com/enunomaduro)**
    -   LinkedIn: **[linkedin.com/in/nunomaduro](https://www.linkedin.com/in/nunomaduro)**
    -   Instagram: **[instagram.com/enunomaduro](https://www.instagram.com/enunomaduro)**
    -   Tiktok: **[tiktok.com/@enunomaduro](https://www.tiktok.com/@enunomaduro)**

## License

**Pokio** was created by **[Nuno Maduro](https://twitter.com/enunomaduro)** under the **[MIT license](https://opensource.org/licenses/MIT)**.
