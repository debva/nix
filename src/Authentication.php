<?php

namespace Debva\Nix;

class Authentication extends Authorization
{
    protected $crpyt;

    protected $token;

    protected $user = [];

    public function __construct()
    {
        $headers = getallheaders();

        $this->crypt = nix('crypt');
        $this->token = isset($headers['Authorization']) ? urldecode($headers['Authorization']) : null;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setUser($user)
    {
        return $this->user = $user;
    }

    public function user($key = null, $search = [], $column = false)
    {
        if (!empty($key) || (is_array($column) && !empty($column))) {
            $user = $this->user;

            foreach (explode('.', $key) as $key) {
                $user = isset($user[$key]) ? $user[$key] : null;
            }

            if (is_array($user) && !empty($search) && is_array($search)) {
                $user = array_filter($user, function ($item) use ($search) {
                    foreach ($search as $key => $value) {
                        if (!isset($item[$key]) || !(is_string($value) ? startsWith($item[$key], $value, true) : $item[$key] === $value)) {
                            return null;
                        }
                    }

                    return $item;
                });

                if ($column === false) return $user;
                $value = array_column($user, $column);
                return $column === false ? $user : (!empty($value) ? $value : null);
            }

            return $user;
        }

        return $this->user;
    }

    protected function generateSignature($algorithm, $data, $privateKey = null)
    {
        $algorithmUsed = substr($algorithm, 0, 2);

        switch ($algorithmUsed) {
            case 'HS':
                return base64_encode($this->crypt->hmac($algorithm, $data));

            case 'PS':
            case 'RS':
                $data = implode('|', [$algorithm, $data]);
                return $this->crypt->generateSignature($data, $privateKey, $algorithm);
        }
    }

    protected function verifySignature($algorithm, $data, $signature, $publicKey = null)
    {
        $algorithmUsed = substr($algorithm, 0, 2);

        switch ($algorithmUsed) {
            case 'HS':
                return base64_encode($this->crypt->hmac($algorithm, $data)) === $signature;

            case 'PS':
            case 'RS':
                $data = implode('|', [$algorithm, $data]);
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

        $class->macro('signature', function ($self, $privateKey = null, $algorithm = 'RS512') use (&$token, &$headers, &$payloads) {
            $headerPayload = implode('.', [
                str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode(array_merge(['alg' => $algorithm, 'typ' => 'JWT'], $headers)))),
                str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payloads)))
            ]);

            $signature = $this->generateSignature($algorithm, $headerPayload, $privateKey);
            $token = base64_encode($this->crypt->encrypt(implode('.', [$headerPayload, $signature]), 'AES-256-CBC'));

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
