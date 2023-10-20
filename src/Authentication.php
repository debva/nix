<?php

namespace Debva\Nix;

class Authentication extends Authorization
{
    protected $crpyt;

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

        $this->crypt = nix('crypt');
    }

    public function __get($name)
    {
        switch ($name) {
            case 'token':
                $headers = getallheaders();
                return isset($headers['Authorization']) ? $headers['Authorization'] : null;

            case 'user':
                return;
        }
    }

    protected function generateSignature($algorithm, $data, $privateKey = null)
    {
        $algorithmUsed = substr($algorithm, 0, 2);
        $algorithm = $this->algorithms[$algorithm];

        switch ($algorithmUsed) {
            case 'HS':
                return base64_encode($this->crypt->hmac($algorithm, $data));

            case 'PS':
            case 'RS':
                $data = implode('|', [$algorithm, $data]);
                $privateKey = $this->crypt->getPrivateKey($privateKey);
                return $this->crypt->generateSignature($data, $privateKey, $algorithm);
        }
    }

    protected function verifySignature($algorithm, $data, $signature, $publicKey = null)
    {
        $algorithmUsed = substr($algorithm, 0, 2);
        $algorithm = $this->algorithms[$algorithm];

        switch ($algorithmUsed) {
            case 'HS':
                return base64_encode($this->crypt->hmac($algorithm, $data)) === $signature;

            case 'PS':
            case 'RS':
                $data = implode('|', [$algorithm, $data]);
                $publicKey = $this->crypt->getPublicKey($publicKey);
                return $this->crypt->verifySignature($data, $signature, $publicKey, $algorithm);
        }
    }

    public function buildToken(\Closure $builder)
    {
        $class = nix('anonymous');
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
                if (in_array($key, ['exp', 'nbf']) && !is_int($value)) {
                    throw new \Exception('Value must be an integer!');
                }

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

        $class->macro('signature', function ($self, $algorithm, $privateKey = null) use (&$token, &$headers, &$payloads) {
            if (!in_array($algorithm, array_keys($this->algorithms))) {
                throw new \Exception('Invalid algorithm!');
            }

            $headerPayload = implode('.', [
                str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode(array_merge(['alg' => $algorithm, 'typ' => 'JWT'], $headers)))),
                str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payloads)))
            ]);

            $signature = $this->generateSignature($algorithm, $headerPayload, $privateKey);
            $token = base64_encode($this->crypt->encrypt(implode('.', [$headerPayload, $signature]), 'AES-256-CBC'));

            return $self;
        });

        $class->macro('save', function ($self, $userId = null, $parentId = null) use (&$token, &$payloads) {
            $table = env('AUTH_TOKEN_TABLE', 'tokens:parent_id,user_id,token,expires_at');
            $table = array_filter(explode(':', $table));

            if (count($table) < 2) {
                throw new \Exception('Auth token table not valid!', 500);
            }

            list($table, $fields) = $table;
            $fields = array_filter(explode(',', $fields));

            if (count($fields) < 3) {
                throw new \Exception('Auth token table not valid!', 500);
            }

            $field = implode(', ', $fields);

            if (!is_null($token)) {
                query(
                    "INSERT INTO {$table} ({$field})
                    SELECT * FROM (SELECT :a AS `{$fields[0]}`, :b AS `{$fields[1]}`, :c AS `{$fields[2]}`, :d AS `{$fields[3]}`) AS temp 
                    WHERE NOT EXISTS (SELECT `{$fields[2]}` FROM {$table} WHERE `{$fields[2]}` = :c)",
                    [
                        'a' => $parentId,
                        'b' => $userId,
                        'c' => md5($token),
                        'd' => isset($payloads['exp']) ? date('Y-m-d H:i:s', $payloads['exp']) : null,
                    ]
                );
            }

            return $self;
        });

        $builder($class);

        return $token;
    }

    public function verify($token, $publicKey = null)
    {
        if (!$this->crypt->isBase64($token)) {
            return false;
        }

        $token = $this->crypt->decrypt(base64_decode($token), 'AES-256-CBC');
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

        if (!$this->verifySignature($algorithm, $data, $signature, $publicKey)) {
            return false;
        }

        return true;
    }

    public function claim($token, \Closure $claims)
    {
        $isVerified = true;
        $payload = $this->parse($token);

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

        $class->macro('get', function ($self, $key) use ($payload) {
            return isset($payload[$key]) ? $payload[$key] : false;
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

    public function parse($token = null)
    {
        $token = is_null($token) ? $this->token : $token;

        if (!$this->crypt->isBase64($token)) {
            return false;
        }

        $token = $this->crypt->decrypt(base64_decode($token), 'AES-256-CBC');
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
