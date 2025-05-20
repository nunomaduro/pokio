<?php

declare(strict_types=1);

namespace Pokio\Runtime\Fork;

use Closure;
use Pokio\Contracts\Result;
use Pokio\Contracts\Runtime;
use Pokio\Environment;
use Pokio\Support\PipePath;
use RuntimeException;

final readonly class ForkRuntime implements Runtime
{
    /**
     * Defers the given callback to be executed asynchronously.
     */
    public function defer(Closure $callback): Result
    {
        $pipePath = PipePath::get();

        if (file_exists($pipePath)) {
            unlink($pipePath);
        }

        if (! posix_mkfifo($pipePath, 0600)) {
            throw new RuntimeException('Failed to create pipe');
        }

        $pid = pcntl_fork();

        if ($pid === -1) {
            throw new RuntimeException('Failed to fork process');
        }

        if ($pid === 0) {
            $result = $callback();
            $pipe = fopen($pipePath, 'w');

            $data = match (Environment::getEncryptionKey() !== null) {
                true => $this->encrypt(serialize($result)),
                false => serialize($result),
            };

            fwrite($pipe, $data);
            fclose($pipe);

            exit(0);
        }

        return new ForkResult($pipePath);
    }

    /**
     * Encrypts the given data using the environment's encryption key.
     */
    private function encrypt(string $data): string
    {
        $initializationVector = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', Environment::getEncryptionKey(), 0, $initializationVector);

        return base64_encode($initializationVector.$encrypted);
    }
}
