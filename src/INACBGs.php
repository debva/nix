<?php

namespace Debva\Nix;

class INACBGs
{
    protected $key = '';

    protected $baseUrl = '';

    public function __construct($baseUrl, $key)
    {
        $this->key = $key;

        $this->baseUrl = $baseUrl;
    }

    public function __invoke($payload)
    {
        $payload = $this->encrypt($payload);

        $response = http()->post($this->baseUrl, [], $payload);

        if (is_null($response)) {
            return false;
        }

        return $this->decrypt($response);
    }

    protected function encrypt($data)
    {
        $key = hex2bin($this->key ? $this->key : '');

        if (mb_strlen($key, '8bit') !== 32) {
            throw new \Exception('Needs a 256-bit key!');
        }

        $chiper = 'AES-256-CBC';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($chiper));
        $encrypted = openssl_encrypt(json_encode($data), $chiper, $key, OPENSSL_RAW_DATA, $iv);
        $signature = mb_substr(hash_hmac('SHA256', $encrypted, $key, true), 0, 10, '8bit');

        return chunk_split(base64_encode("{$signature}{$iv}{$encrypted}"));
    }

    protected function compare($a, $b)
    {
        if (strlen($a) !== strlen($b)) {
            return false;
        }

        $result = 0;
        for ($i = 0; $i < strlen($a); $i++) {
            $result |= ord($a[$i]) ^ ord($b[$i]);
        }

        return $result == 0;
    }

    protected function decrypt($string)
    {
        $key = hex2bin($this->key ? $this->key : '');

        if (mb_strlen($key, '8bit') !== 32) {
            throw new \Exception('Needs a 256-bit key!');
        }

        $first = strpos($string, "\n") + 1;
        $last = strrpos($string, "\n") - 1;
        $string = trim(substr($string, $first, strlen($string) - $first - $last));

        $chiper = 'AES-256-CBC';
        $size = openssl_cipher_iv_length($chiper);

        $decoded = base64_decode($string);
        $signature = mb_substr($decoded, 0, 10, '8bit');

        $iv = mb_substr($decoded, 10, $size, '8bit');
        $encrypted = mb_substr($decoded, $size + 10, NULL, '8bit');
        $calc_signature = mb_substr(hash_hmac('SHA256', $encrypted, $key, true), 0, 10, '8bit');

        if (!$this->compare($signature, $calc_signature)) {
            return new \Exception('SIGNATURE_NOT_MATCH');
        }

        $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return json_decode($decrypted, true);
    }
}
