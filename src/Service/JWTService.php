<?php

namespace App\Service;

class JWTService
{
    // on genere le token

    /**
     * Summary of generate
     * @param array $header
     * @param array $payload
     * @param string $secret
     * @param int $validity
     * @return string
     */
    public function generate(array $header, array $payload, string $secret, int $validity = 10800): string
    {

        if ($validity > 0) {
            $now = new \DateTimeImmutable();
            $exp = $now->getTimestamp() + $validity;
            $payload['iat'] = $now->getTimestamp();
            $payload['exp'] = $exp;
        }

        // on encode base64
        $base64Header = base64_encode(json_encode($header));
        $base64Payload = base64_encode(json_encode($payload));

        // on nettoie les strings
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], $base64Header);
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], $base64Payload);

        // on verifie la signature
        $secret = base64_encode($secret);
        $signature = hash_hmac(
            'sha256',
            $base64Header . '.' . $base64Payload,
            $secret,
            true
        );
        $base64Signature = base64_encode($signature);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], $base64Signature);

        // on créé le token
        $jwt = $base64Header . '.' . $base64Payload . '.' . $base64Signature;
        return $jwt;
    }

    // on verifie la conformité du token
    public function isValid(string $token): bool
    {
        return preg_match(
            '/^[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+$/',
            $token
        ) === 1;
    }
    // on recupere le payload du token
    public function getPayload(string $token): array
    {
        return json_decode(base64_decode(explode('.', $token)[1]), true);
    }
    // on recupere le header du token
    public function getHeader(string $token): array
    {
        return json_decode(base64_decode(explode('.', $token)[0]), true);
    }
    // on verifie l'expiration' du token
    public function isExpired(string $token): bool
    {
        return $this->getPayload($token)['exp'] < (new \DateTimeImmutable())->getTimestamp();
    }
    // on verifie la signature du token
    public function check(string $token, string $secret): bool
    {
        $header = $this->getHeader($token);
        $payload = $this->getPayload($token);

        $verif = $this->generate($header, $payload, $secret, 0);

        return $verif === $token;
    }
}
