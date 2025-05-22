<?php

declare(strict_types=1);

namespace Pokio\Runtime\Fork;

use RuntimeException;

/**
 * @internal
 */
final readonly class IPC
{
    /**
     * Creates a new temporary file for IPC.
     */
    private function __construct(
        private string $filepath,
    ) {
        //
    }

    public static function create(): self
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'pokio_ipc_');

        if ($tmpFile === false) {
            throw new RuntimeException('Failed to create temporary file for IPC');
        }

        return new self($tmpFile);
    }

    /**
     * Writes data to the temporary file.
     */
    public function put(string $data): void
    {
        $bytesWritten = file_put_contents($this->filepath, $data, LOCK_EX);

        if ($bytesWritten === false) {
            throw new RuntimeException('Failed to write data to temporary file');
        }
    }

    /**
     * Reads and deletes the temporary file.
     */
    public function pop(): string
    {
        if (!file_exists($this->filepath)) {
            throw new RuntimeException('Temporary file does not exist');
        }

        $data = file_get_contents($this->filepath);

        if ($data === false) {
            throw new RuntimeException('Failed to read from temporary file');
        }

        // Clean up the temporary file
        unlink($this->filepath);

        return $data;
    }

    /**
     * Get the file path (useful for debugging or manual cleanup).
     */
    public function getFilepath(): string
    {
        return $this->filepath;
    }

    /**
     * Check if the temporary file exists.
     */
    public function exists(): bool
    {
        return file_exists($this->filepath);
    }

    /**
     * Manual cleanup in case pop() wasn't called.
     */
    public function cleanup(): void
    {
        if (file_exists($this->filepath)) {
            unlink($this->filepath);
        }
    }

    /**
     * Destructor to ensure cleanup.
     */
    public function __destruct()
    {
        $this->cleanup();
    }
}
