<?php

namespace RandomPHP\Hashing\Algorithm;

use RandomPHP\Hashing\Abstract\Argon2HashingAlgorithm;

final class Argon2IHashingAlgorithm extends Argon2HashingAlgorithm
{
    protected static function getAlgo(): string
    {
        return PASSWORD_ARGON2I;
    }
}