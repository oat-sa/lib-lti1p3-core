<?php

/**
 * A stand-in for codercat/jwk-to-pem's converter, which is no longer a
 * dependency, so tests can prove one is still accepted where it used to be.
 */

declare(strict_types=1);

namespace CoderCat\JWKToPEM;

if (!class_exists(JWKConverter::class)) {
    class JWKConverter
    {
        /** @var string[] */
        public $converted = [];

        public function toPEM(array $jwk): string
        {
            $this->converted[] = $jwk['kid'] ?? '';

            return file_get_contents(__DIR__ . '/../Key/RSA/public.key');
        }
    }
}
