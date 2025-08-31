<p align="center">
    <a href="https://youtu.be/JUDQuymlsh0" target="_blank">
        <img src="/art/banner.jpg" alt="Overview Pokio" style="width:70%;">
    </a>
</p>

> **Caution**: This package is a **work in progress** and it manipulates process lifecycles using low-level and potentially unsafe techniques such as FFI for inter-process communication, and preserving state across process spawns. It is intended strictly for internal use (e.g., performance optimizations in Pest). **Don't use this in production or use at your own risk**—no guarantees are provided.

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

Behind-the-scenes, Pokio uses the **[PCNTL](https://www.php.net/manual/en/book.pcntl.php)** extension to fork the current process and run the given closure in a child process. This allows you to run multiple tasks concurrently, without blocking the main process.

Also, for communication between the parent and child processes, Pokio uses **[FFI](https://www.php.net/manual/en/book.ffi.php)** to create a shared memory segment, which allows you to share data between processes fast and efficiently.

However, unlike other libraries, if **PCNTL** or **FFI** are not available, Pokio will automatically fall back to sequential execution, so you can still use it without any issues.

## Installation

> **Requires [PHP 8.3+](https://php.net/releases/)**.

⚡️ Get started by requiring the package using [Composer](https://getcomposer.org):

```bash
composer require nunomaduro/pokio:^0.1
```

## Usage

- `async`

The `async` global function returns a promise that will eventually resolve the value returned by the given closure.

```php
$promise = async(function () {
    return 1 + 1;
});

var_dump(await($promise)); // int(2)
```

Similar to other promise libraries, Pokio allows you to chain methods to the promise (like `then`, `catch`, etc.).

The `then` method will be called when the promise resolves successfully. It accepts a closure that will receive the resolved value as its first argument.

```php
$promise = async(fn (): int => 1 + 2)
    ->then(fn ($result): int => $result + 2)
    ->then(fn ($result): int => $result * 2);

$result = await($promise);
var_dump($result); // int(10)
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

Similar to the `catch` method, you may also chain a `finally` method to the promise, which will be called regardless of whether the promise resolves successfully or throws an exception.

```php
$promise = async(function (): void {
    throw new RuntimeException('Exception 1');
})->finally(function () use (&$called): void {
    echo "Finally called\n";
});
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

- `await`

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

Instead of using the await function, you can also invoke the promise directly, which will return the resolved value of the promise.

```php
$promise = async(fn (): int => 1 + 2);

$result = $promise();

var_dump($result); // int(3)
```

## Follow Nuno

- Follow the creator Nuno Maduro:
    - YouTube: **[youtube.com/@nunomaduro](https://youtube.com/@nunomaduro)** — Videos every week
    - Twitch: **[twitch.tv/nunomaduro](https://twitch.tv/nunomaduro)** — Live coding on Mondays, Wednesdays, and Fridays at 9PM UTC
    - Twitter / X: **[x.com/enunomaduro](https://x.com/enunomaduro)**
    - LinkedIn: **[linkedin.com/in/nunomaduro](https://www.linkedin.com/in/nunomaduro)**
    - Instagram: **[instagram.com/enunomaduro](https://www.instagram.com/enunomaduro)**
    - Tiktok: **[tiktok.com/@enunomaduro](https://www.tiktok.com/@enunomaduro)**

## License

**Pokio** was created by **[Nuno Maduro](https://twitter.com/enunomaduro)** under the **[MIT license](https://opensource.org/licenses/MIT)**.
