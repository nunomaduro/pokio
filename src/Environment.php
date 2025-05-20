<?php

declare(strict_types=1);

namespace Pokio;

use Pokio\Contracts\Runtime;
use Pokio\Runtime\Fork\ForkRuntime;
use Pokio\Runtime\Sync\SyncRuntime;

final class Environment
{
    /**
     * The environment's runtime.
     */
    public static ?Runtime $runtime = null;

    /**
     * The environment's encryption key.
     */
    private static ?string $encryptionKey = null;

    /**
     * The environment's runtime.
     */
    public static function useFork(): void
    {
        if (! extension_loaded('pcntl') || ! extension_loaded('posix')) {
            throw new \RuntimeException('The pcntl and posix extensions are required to use the fork runtime.');
        }

        self::$runtime = new ForkRuntime;
    }

    /**
     * The environment's runtime.
     */
    public static function useSync(): void
    {
        self::$runtime = new SyncRuntime;
    }

    /**
     * Resolves the environment's runtime.
     */
    public static function runtime(): Runtime
    {
        $areExtensionsAvailable = extension_loaded('pcntl') && extension_loaded('posix');

        return self::$runtime ??= $areExtensionsAvailable
            ? new ForkRuntime
            : new SyncRuntime;
    }

    /**
     * Sets the environment's encryption key.
     * Hashes the given key using SHA-256 to ensure a 32-byte key for encryption.
     */
    public static function setEncryptionKey(string $key): void
    {
        self::$encryptionKey = hash('sha256', $key, false);
    }

    /**
     * Gets the environment's encryption key.
     */
    public static function getEncryptionKey(): ?string
    {
        return self::$encryptionKey;
    }
}
