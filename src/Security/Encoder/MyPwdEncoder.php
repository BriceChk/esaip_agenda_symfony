<?php


namespace App\Security\Encoder;

use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class MyPwdEncoder implements PasswordEncoderInterface {

    public function encodePassword(string $raw, ?string $salt): string {
        return $raw;
    }

    public function isPasswordValid(string $encoded, string $raw, ?string $salt): bool {
        return true;
    }

    public function needsRehash(string $encoded): bool {
        return false;
    }
}