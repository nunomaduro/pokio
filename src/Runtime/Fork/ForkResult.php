<?php

namespace Pokio\Runtime\Fork;

use Pokio\Contracts\Result;
use Pokio\Support\Encryption;

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
        $encryptedData = stream_get_contents($pipe);
        fclose($pipe);

        if (file_exists($this->pipePath)) {
            unlink($this->pipePath);
        }

        $this->resolved = true;

        // Decrypt the data before unserializing
        $serialized = Encryption::decrypt($encryptedData);

        return $this->result = unserialize($serialized);
    }
}
