<?php

declare(strict_types=1);

namespace Pokio\Support;

use RuntimeException;

/**
 * An encryption/decryption utility for Pokio.
 */
final class Encryption
{
    /**
     * The encryption method used.
     */
    private const string CIPHER_METHOD = 'aes-256-gcm';

    /**
     * Authentication tag length.
     */
    private const int TAG_LENGTH = 16;

    /**
     * Environment variable for the encryption key.
     */
    private const string ENV_KEY = 'POKIO_ENCRYPTION_KEY';

    /**
     * Encrypts the given data.
     *
     * @throws RuntimeException If encryption fails.
     */
    public static function encrypt(string $data, ?string $key = null): string
    {
        $key ??= self::getEncryptionKey();
        $ivlen = openssl_cipher_iv_length(self::CIPHER_METHOD);

        if (function_exists('random_bytes')) {
            $iv = random_bytes($ivlen);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $iv = openssl_random_pseudo_bytes($ivlen, $strong);
            if (! $strong) {
                throw new RuntimeException('Failed to generate secure IV');
            }
        } else {
            throw new RuntimeException('No source of secure random available');
        }

        $tag = '';

        $encrypted = openssl_encrypt(
            $data,
            self::CIPHER_METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($encrypted === false) {
            throw new RuntimeException('Encryption failed: '.openssl_error_string());
        }

        $result = [
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'data' => base64_encode($encrypted),
        ];

        return base64_encode(json_encode($result));
    }

    /**
     * Decrypts the given data.
     *
     * @throws RuntimeException If decryption fails or the data is invalid.
     */
    public static function decrypt(string $data, ?string $key = null): string
    {
        $key ??= self::getEncryptionKey();

        try {
            $decoded = base64_decode($data, true);
            if ($decoded === false) {
                throw new RuntimeException('Invalid base64 encoded data');
            }

            $parts = json_decode($decoded, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Invalid encrypted data format');
            }

            if (! isset($parts['iv'], $parts['tag'], $parts['data'])) {
                throw new RuntimeException('Missing required encryption components');
            }

            $iv = base64_decode((string) $parts['iv'], true);
            $tag = base64_decode((string) $parts['tag'], true);
            $encrypted = base64_decode((string) $parts['data'], true);

            if ($iv === false || $tag === false || $encrypted === false) {
                throw new RuntimeException('Invalid encryption components');
            }

            $decrypted = openssl_decrypt(
                $encrypted,
                self::CIPHER_METHOD,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
                ''
            );

            if ($decrypted === false) {
                throw new RuntimeException('Decryption failed: '.openssl_error_string());
            }

            return $decrypted;
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new RuntimeException('Decryption error: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Gets the encryption key from environment or generates a secure one.
     *
     * @throws RuntimeException If key requirements aren't met.
     */
    public static function getEncryptionKey(): string
    {

        $envKey = getenv(self::ENV_KEY);

        if ($envKey !== false && strlen($envKey) >= 32) {

            return hash('sha256', $envKey, true);
        }

        $keyFile = self::getKeyFilePath();
        if (file_exists($keyFile)) {
            $fileKey = trim(file_get_contents($keyFile));
            if (strlen($fileKey) >= 32) {
                return hash('sha256', $fileKey, true);
            }
        }

        if (getenv('POKIO_AUTO_GENERATE_KEY') === 'true') {
            return self::generateAndPersistKey();
        }

        trigger_error(
            'No secure encryption key available. Set POKIO_ENCRYPTION_KEY environment variable'.
            ' or allow auto-generation with POKIO_AUTO_GENERATE_KEY=true',
            E_USER_WARNING
        );

        return hash('sha256', sys_get_temp_dir().get_current_user().__DIR__, true);
    }

    /**
     * Generates a secure key and persists it if possible.
     */
    private static function generateAndPersistKey(): string
    {

        $randomBytes = function_exists('random_bytes')
            ? random_bytes(32)
            : openssl_random_pseudo_bytes(32);

        $rawKey = bin2hex($randomBytes);

        $keyFile = self::getKeyFilePath();
        $directory = dirname($keyFile);

        if (! is_dir($directory) && is_writable(dirname($directory))) {
            mkdir($directory, 0700, true);
        }

        if (is_dir($directory) && is_writable($directory)) {
            file_put_contents($keyFile, $rawKey);
            chmod($keyFile, 0600);
        }

        return hash('sha256', $rawKey, true);
    }

    /**
     * Gets the path to the key file.
     */
    private static function getKeyFilePath(): string
    {
        $configPath = getenv('POKIO_CONFIG_PATH');
        $basePath = $configPath !== false
            ? $configPath
            : (sys_get_temp_dir().DIRECTORY_SEPARATOR.'pokio');

        return $basePath.DIRECTORY_SEPARATOR.'.encryption_key';
    }

    /**
     * Verifies the current encryption setup.
     *
     * @return array<string, mixed> Status information
     */
    public static function validateSetup(): array
    {
        $status = [
            'cipher_supported' => in_array(self::CIPHER_METHOD, openssl_get_cipher_methods()),
            'key_source' => 'fallback',
            'key_length' => 0,
            'security_level' => 'low',
        ];

        $envKey = getenv(self::ENV_KEY);
        if ($envKey !== false && strlen($envKey) >= 32) {
            $status['key_source'] = 'environment';
            $status['key_length'] = strlen($envKey);
            $status['security_level'] = 'high';
        }

        $keyFile = self::getKeyFilePath();
        if (file_exists($keyFile)) {
            $fileKey = trim(file_get_contents($keyFile));
            $status['key_file_exists'] = true;
            $status['key_file_permissions'] = substr(sprintf('%o', fileperms($keyFile)), -4);
            $status['key_file_secure'] = (fileperms($keyFile) & 0177) === 0;

            if (strlen($fileKey) >= 32 && $status['key_source'] === 'fallback') {
                $status['key_source'] = 'file';
                $status['key_length'] = strlen($fileKey);
                $status['security_level'] = $status['key_file_secure'] ? 'medium' : 'low';
            }
        }

        return $status;
    }
}
