<?php

namespace Debva\Nix;

class Inacbg
{
    protected $key = '';

    protected $baseurl = '';

    public function __construct($options, $isProduction = false)
    {
        extract($options, EXTR_PREFIX_ALL, 'opts');

        $this->key = $opts_key;
        
        $this->baseurl = $opts_baseurl;
    }
    
    public function __invoke($service, $body = [])
    {
        // dd(json_encode($body));
        $body = $this->inacbg_encrypt(json_encode($body), $this->key);
        
        $response = (new Http)->{'post'}("{$this->baseurl}", [
            'Content-Type: application/json;'
        ], $body);
        
        $first = strpos($response, "\n") + 1;
        $last = strrpos($response, "\n") - 1;
        $response = trim(substr(
            $response,
            $first,
            strlen($response) - $first - $last
        ));
        
        $response = $this->inacbg_decrypt($response,$this->key);
        
        dd($response);
        

        return $response;
    }

    public function inacbg_encrypt($data, $key)
    {
        $key = hex2bin($key);

        if (mb_strlen($key, "8bit") !== 32) {
            throw new Exception("Needs a 256-bit key!");
        }

        $iv_size = openssl_cipher_iv_length("aes-256-cbc");
        $iv = openssl_random_pseudo_bytes($iv_size);

        $encrypted = openssl_encrypt(
            json_encode($data),
            "aes-256-cbc",
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        $signature = mb_substr(hash_hmac(
            "sha256",
            $encrypted,
            $key,
            true
        ), 0, 10, "8bit");

        $encoded = chunk_split(base64_encode($signature . $iv . $encrypted));
        return $encoded;
    }

    public function inacbg_compare($a, $b)
    {
        if (strlen($a) !== strlen($b)) return false;

        $result = 0;
        for ($i = 0; $i < strlen($a); $i++) {
            $result |= ord($a[$i]) ^ ord($b[$i]);
        }

        return $result == 0;
    }

    public function inacbg_decrypt($str, $strkey)
    {
        $key = hex2bin($strkey);

        if (mb_strlen($key, "8bit") !== 32) {
            throw new Exception("Needs a 256-bit key!");
        }

        $iv_size = openssl_cipher_iv_length("aes-256-cbc");

        $decoded = base64_decode($str);
        $signature = mb_substr($decoded, 0, 10, "8bit");
        $iv = mb_substr($decoded, 10, $iv_size, "8bit");
        $encrypted = mb_substr($decoded, $iv_size + 10, NULL, "8bit");

        $calc_signature = mb_substr(hash_hmac(
            "sha256",
            $encrypted,
            $key,
            true
        ), 0, 10, "8bit");


        if (!$this->inacbg_compare($signature, $calc_signature)) {
            return "SIGNATURE_NOT_MATCH";
        }

        $decrypted = openssl_decrypt(
            $encrypted,
            "aes-256-cbc",
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return json_decode($decrypted, true);
    }

}
