<?php

namespace Debva\Nix;

class Authentication extends Authorization
{
    protected $algorithms = [];

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
    }

    protected function createSignature($algorithm, $data, $signingKey, $privateKey = null)
    {
        $algorithmUsed = substr($algorithm, 0, 2);

        switch ($algorithmUsed) {
            case 'HS':
                return base64_encode(hash_hmac($this->algorithms[$algorithm], $data, $signingKey, true));

            case 'PS':
            case 'RS':
                if (!file_exists($privateKey)) {
                    throw new \Exception('Private key not found!');
                }

                $privateKey = openssl_pkey_get_private(file_get_contents($privateKey));
                if ($privateKey === false) {
                    throw new \Exception('Failed to load private key!');
                }

                $data = implode('|', [$algorithm, $data, $signingKey]);
                if (!openssl_sign($data, $signature, $privateKey, $this->algorithms[$algorithm])) {
                    throw new \Exception('Failed to create signature!');
                }

                if (PHP_VERSION <= '8.0.0') {
                    openssl_free_key($privateKey);
                }

                return rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        }
    }

    protected function verifySignature($algorithm, $data, $signature, $signingKey, $publicKey = null)
    {
        $algorithmUsed = substr($algorithm, 0, 2);

        switch ($algorithmUsed) {
            case 'HS':
                return base64_encode(hash_hmac($this->algorithms[$algorithm], $data, $signingKey, true)) === $signature;

            case 'PS':
            case 'RS':
                if (!file_exists($publicKey)) {
                    throw new \Exception('Public key not found!');
                }

                $publicKey = openssl_pkey_get_public(file_get_contents($publicKey));
                if ($publicKey === false) {
                    throw new \Exception('Failed to load public key!');
                }

                $data = implode('|', [$algorithm, $data, $signingKey]);
                $signature = base64_decode(strtr($signature, '-_', '+/'));
                $isVerified = openssl_verify($data, $signature, $publicKey, $this->algorithms[$algorithm]);

                if (PHP_VERSION <= '8.0.0') {
                    openssl_free_key($publicKey);
                }

                return $isVerified === 1;
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

        $class->macro('signed', function ($self, $algorithm, $signingKey, $privateKey = null) use (&$token, &$headers, &$payloads) {
            if (!in_array($algorithm, array_keys($this->algorithms))) {
                throw new \Exception('Invalid algorithm!');
            }

            $headerPayload = implode('.', [
                str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode(array_merge(['alg' => $algorithm, 'typ' => 'JWT'], $headers)))),
                str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payloads)))
            ]);

            $iv = substr($signingKey, 0, 15);
            $iv = strlen($iv) < 16 ? str_pad($signingKey, 16, $signingKey) : $iv;

            $signature = $this->createSignature($algorithm, $headerPayload, $signingKey, $privateKey);
            $token = base64_encode(openssl_encrypt(implode('.', [$headerPayload, $signature]), 'AES-256-CBC', $signingKey, 0, $iv));

            return $self;
        });

        $builder($class);

        return $token;
    }

    public function parseToken($token, $signingKey)
    {
        $iv = substr($signingKey, 0, 15);
        $iv = strlen($iv) < 16 ? str_pad($signingKey, 16, $signingKey) : $iv;
        $token = openssl_decrypt(base64_decode($token), 'AES-256-CBC', $signingKey, 0, $iv);

        list($header, $payload, $signature) = explode('.', $token);
        if (!isset($header, $payload, $signature)) {
            return false;
        }

        return json_decode(base64_decode($payload), true);
    }

    public function verifyToken($token, $signingKey, $privateKey = null)
    {
        $iv = substr($signingKey, 0, 15);
        $iv = strlen($iv) < 16 ? str_pad($signingKey, 16, $signingKey) : $iv;
        $token = openssl_decrypt(base64_decode($token), 'AES-256-CBC', $signingKey, 0, $iv);

        list($header, $payload, $signature) = explode('.', $token);
        if (!isset($header, $payload, $signature)) {
            return false;
        }

        $time = time();
        $data = implode('.', [$header, $payload]);
        $header = json_decode(base64_decode($header), true);
        $payload = json_decode(base64_decode($payload), true);

        $algorithm = $header['alg'];
        if (!in_array($algorithm, array_keys($this->algorithms))) {
            throw new \Exception('Invalid algorithm!');
        }

        if (!$header && isset($header['alg'], $header['typ'])) {
            return false;
        }

        if (isset($payload['exp']) && $payload['exp'] < $time) {
            return false;
        }

        if (isset($payload['nbf']) && $payload['nbf'] > $time) {
            return false;
        }

        if (!$this->verifySignature($algorithm, $data, $signature, $signingKey, $privateKey)) {
            return false;
        }

        return true;
    }

    public function verifyClaims($token, $signingKey, $claims = [])
    {
        $isVerified = true;
        $payload = $this->parseToken($token, $signingKey);

        foreach ($claims as $claim => $value) {
            if (!in_array($claim, array_keys($payload)) || (isset($payload[$claim]) && $payload[$claim] !== $value)) {
                $isVerified = false;
                break;
            }
        }

        return $isVerified;
    }
}
