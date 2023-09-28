<?php

namespace Debva\Nix;

class Authentication extends Authorization
{
    protected $algorithms = [];

    protected $crpyt;

    public function __construct()
    {
        $this->algorithms = [
            'HS256'     => 'sha256',
            'HS384'     => 'sha384',
            'HS512'     => 'sha512',
            'PS256'     => OPENSSL_ALGO_SHA256,
            'PS384'     => OPENSSL_ALGO_SHA384,
            'PS512'     => OPENSSL_ALGO_SHA512,
            'RS256'     => OPENSSL_ALGO_SHA256,
            'RS384'     => OPENSSL_ALGO_SHA384,
            'RS512'     => OPENSSL_ALGO_SHA512,
        ];

        $this->crypt = new Cryptography;
    }

    protected function generateSignature($algorithm, $data, $signingKey, $privateKey = null)
    {
        $algorithmUsed = substr($algorithm, 0, 2);
        $algorithm = $this->algorithms[$algorithm];

        switch ($algorithmUsed) {
            case 'HS':
                return base64_encode($this->crypt->hmac($algorithm, $data, $signingKey));

            case 'PS':
            case 'RS':
                $data = implode('|', [$algorithm, $data, $signingKey]);
                $privateKey = $this->crypt->getPrivateKey($privateKey);
                return $this->crypt->generateSignature($data, $privateKey, $algorithm);
        }
    }

    protected function verifySignature($algorithm, $data, $signature, $signingKey, $publicKey = null)
    {
        $algorithmUsed = substr($algorithm, 0, 2);
        $algorithm = $this->algorithms[$algorithm];

        switch ($algorithmUsed) {
            case 'HS':
                return base64_encode($this->crypt->hmac($algorithm, $data, $signingKey)) === $signature;

            case 'PS':
            case 'RS':
                $data = implode('|', [$algorithm, $data, $signingKey]);
                $publicKey = $this->crypt->getPublicKey($publicKey);
                return $this->crypt->verifySignature($data, $signature, $publicKey, $algorithm);
        }
    }

    public function buildToken(\Closure $builder)
    {
        $class = new Anonymous;
        $macros = [
            'iss' => 'issuedBy',
            'sub' => 'subject',
            'aud' => 'permittedFor',
            'exp' => 'expiresAt',
            'nbf' => 'canOnlyBeUsedAfter',
            'iat' => 'issuedAt',
            'jti' => 'identifiedBy'
        ];

        $token = null;
        $headers = $payloads = [];

        foreach ($macros as $key => $macro) {
            $class->macro($macro, function ($self, $value) use (&$payloads, $key) {
                $payloads[$key] = $value;
                return $self;
            });
        }

        $class->macro('withHeader', function ($self, $key, $value) use (&$headers) {
            $headers[$key] = $value;
            return $self;
        });

        $class->macro('withClaim', function ($self, $key, $value) use (&$payloads) {
            $payloads[$key] = $value;
            return $self;
        });

        $class->macro('signature', function ($self, $algorithm, $signingKey, $privateKey = null) use (&$token, &$headers, &$payloads) {
            if (!in_array($algorithm, array_keys($this->algorithms))) {
                throw new \Exception('Invalid algorithm!');
            }

            $headerPayload = implode('.', [
                str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode(array_merge(['alg' => $algorithm, 'typ' => 'JWT'], $headers)))),
                str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payloads)))
            ]);

            $signature = $this->generateSignature($algorithm, $headerPayload, $signingKey, $privateKey);
            $token = base64_encode($this->crypt->encrypt(implode('.', [$headerPayload, $signature]), 'AES-256-CBC', $signingKey));

            return $self;
        });

        $builder($class);

        return $token;
    }

    public function verify($token, $signingKey, $publicKey = null)
    {
        if (!$this->crypt->isBase64($token)) {
            return false;
        }

        $token = $this->crypt->decrypt(base64_decode($token), 'AES-256-CBC', $signingKey);
        if (!$token) {
            return false;
        }

        list($header, $payload, $signature) = explode('.', $token);
        if (!isset($header, $payload, $signature)) {
            return false;
        }

        $time = time();
        $data = implode('.', [$header, $payload]);
        $header = json_decode(base64_decode($header), true);
        $payload = json_decode(base64_decode($payload), true);

        if (!$header || !isset($header['alg'], $header['typ'])) {
            return false;
        }

        $algorithm = $header['alg'];
        if (!in_array($algorithm, array_keys($this->algorithms))) {
            throw new \Exception('Invalid algorithm!');
        }

        if (isset($payload['exp']) && $payload['exp'] < $time) {
            return false;
        }

        if (isset($payload['nbf']) && $payload['nbf'] > $time) {
            return false;
        }

        if (!$this->verifySignature($algorithm, $data, $signature, $signingKey, $publicKey)) {
            return false;
        }

        return true;
    }

    public function claim($token, $signingKey, \Closure $claims)
    {
        $isVerified = true;
        $payload = $this->parse($token, $signingKey);

        if (!$payload) {
            return false;
        }

        $class = new Anonymous;

        $macros = [
            'iss' => 'issuedBy',
            'sub' => 'subject',
            'aud' => 'permittedFor',
            'iat' => 'issuedAt',
            'jti' => 'identifiedBy'
        ];

        $token = null;
        $payloads = [];

        foreach ($macros as $key => $macro) {
            $class->macro($macro, function ($self, $value) use (&$payloads, $key) {
                $payloads[$key] = $value;
                return $self;
            });
        }

        $class->macro('check', function ($self, $key, $value) use (&$payloads) {
            $payloads[$key] = $value;
            return $self;
        });

        $claims($class);

        foreach ($payloads as $key => $value) {
            if (!in_array($key, array_keys($payload)) || (isset($payload[$key]) && $payload[$key] !== $value)) {
                $isVerified = false;
                break;
            }
        }

        return $isVerified;
    }

    public function parse($token, $signingKey)
    {
        if (!$this->crypt->isBase64($token)) {
            return false;
        }

        $token = $this->crypt->decrypt(base64_decode($token), 'AES-256-CBC', $signingKey);
        if (!$token) {
            return false;
        }

        list($header, $payload, $signature) = explode('.', $token);
        if (!isset($header, $payload, $signature)) {
            return false;
        }

        return json_decode(base64_decode($payload), true);
    }
}
