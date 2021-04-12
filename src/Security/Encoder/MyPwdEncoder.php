<?php


namespace App\Security\Encoder;


use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class MyPwdEncoder implements PasswordEncoderInterface {

    private $key;

    public function __construct(string $key) {
        $this->key = base64_decode($key);
    }

    public function encodePassword(string $raw, ?string $salt): string {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return base64_encode(
            $nonce.
            sodium_crypto_secretbox(
                $raw,
                $nonce,
                $this->key
            )
        );
    }

    public function decodePassword(string $encoded) {
        $decoded = base64_decode($encoded);
        $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

        return sodium_crypto_secretbox_open(
            $ciphertext,
            $nonce,
            $this->key
        );
    }

    public function isPasswordValid(string $encoded, string $raw, ?string $salt): bool {
        return $this->encodePassword($raw, null) == $encoded;
    }

    public function needsRehash(string $encoded): bool {
        return false;
    }
}