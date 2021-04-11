<?php


namespace App\Security\Encoder;


use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class MyPwdEncoder implements PasswordEncoderInterface {

    public function encodePassword(string $raw, ?string $salt) {
        return $raw . "test";
    }

    public function isPasswordValid(string $encoded, string $raw, ?string $salt) {
        return $encoded == $raw . 'test';
    }

    public function needsRehash(string $encoded): bool {
        return false;
    }
}