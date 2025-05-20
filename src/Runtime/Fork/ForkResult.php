<?php

namespace Pokio\Runtime\Fork;

use Pokio\Contracts\Result;
use Pokio\Environment;

/**
 * Represents the result of a forked process.
 */
final class ForkResult implements Result
{
    /**
     * The result of the forked process, if any.
     */
    private mixed $result = null;

    /**
     * Indicates whether the result has been resolved.
     */
    private bool $resolved = false;

    /**
     * Creates a new fork result instance.
     */
    public function __construct(
        private readonly string $pipePath,
    ) {
        //
    }

    /**
     * The result of the asynchronous operation.
     */
    public function get(): mixed
    {
        if ($this->resolved) {
            return $this->result;
        }

        $pipe = fopen($this->pipePath, 'r');

        stream_set_blocking($pipe, true);
        $contents = stream_get_contents($pipe);

        fclose($pipe);

        if (file_exists($this->pipePath)) {
            unlink($this->pipePath);
        }

        $this->resolved = true;

        return $this->result = match (Environment::getEncryptionKey() !== null) {
            true => unserialize($this->decrypt($contents)),
            false => unserialize($contents),
        };
    }

    /**
     * Decrypts the given data using the environment's encryption key.
     */
    private function decrypt(string $data): string
    {
        $contents = base64_decode($data);

        $initializationVector = substr($contents, 0, 16);
        $encrypted = substr($contents, 16);

        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', Environment::getEncryptionKey(), 0, $initializationVector);

        if ($decrypted === false) {
            throw new \RuntimeException('Failed to decrypt data');
        }

        return $decrypted;
    }
}
