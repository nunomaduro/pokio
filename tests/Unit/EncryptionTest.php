<?php

use Pokio\Support\Encryption;

test('encryption and decryption works correctly', function (): void {
    $originalData = 'Hello, world!';

    $encrypted = Encryption::encrypt($originalData);
    expect($encrypted)->not->toBe($originalData);

    $decrypted = Encryption::decrypt($encrypted);
    expect($decrypted)->toBe($originalData);
});

test('encryption with custom key works correctly', function (): void {
    $originalData = 'Secret message';
    $customKey = 'my-custom-key-for-testing';

    $encrypted = Encryption::encrypt($originalData, $customKey);
    $decrypted = Encryption::decrypt($encrypted, $customKey);

    expect($decrypted)->toBe($originalData);
});

test('decryption fails with incorrect key', function (): void {
    $originalData = 'Secret message';
    $correctKey = 'correct-key';
    $incorrectKey = 'incorrect-key';

    $encrypted = Encryption::encrypt($originalData, $correctKey);
    $decrypted = Encryption::decrypt($encrypted, $incorrectKey);
    expect($decrypted)->not->toBe($originalData);
});
