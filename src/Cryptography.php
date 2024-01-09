<?php

namespace Debva\Nix;

class Cryptography
{
    public function isBase64($value)
    {
        $decoded = base64_decode($value, true);
        return ($decoded !== false && base64_encode($decoded) === $value);
    }

    public function hmac($algo, $data, $binary = true)
    {
        $key = getenv('APP_KEY') ? getenv('APP_KEY') : 'NIX_SECRET';
        return hash_hmac($algo, $data, $key, $binary);
    }

    public function bcrypt($data)
    {
        return password_hash($data, PASSWORD_BCRYPT);
    }

    public function getPrivateKey($privateKeyFile)
    {
        if (!file_exists($privateKeyFile)) {
            throw new \Exception('Private key file does not exist', 500);
        }

        $privateKey = openssl_pkey_get_private(file_get_contents($privateKeyFile));

        if ($privateKey === false) {
            throw new \Exception('Failed to load private key!', 500);
        }

        return $privateKey;
    }

    public function getPublicKey($publicKeyFile)
    {
        if (!file_exists($publicKeyFile)) {
            throw new \Exception('Public key file does not exist', 500);
        }

        $publicKey = openssl_pkey_get_public(file_get_contents($publicKeyFile));

        if ($publicKey === false) {
            throw new \Exception('Failed to load public key!', 500);
        }

        return $publicKey;
    }

    public function generateSignature($data, $privateKey, $algorithm)
    {
        if (!openssl_sign($data, $signature, $privateKey, $algorithm)) {
            throw new \Exception('Failed to generate signature!', 500);
        }

        if (PHP_VERSION <= '8.0.0') {
            openssl_free_key($privateKey);
        }

        return rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    }

    public function verifySignature($data, $signature, $publicKey, $algorithm)
    {
        $signature = base64_decode(strtr($signature, '-_', '+/'));
        $isVerified = openssl_verify($data, $signature, $publicKey, $algorithm);

        if (PHP_VERSION <= '8.0.0') {
            openssl_free_key($publicKey);
        }

        return $isVerified === 1;
    }

    public function generateRSAKey($config, &$privateKeyPEM, &$publicKeyPEM)
    {
        $config = array_merge([
            "digest_alg"        => "SHA512",
            'private_key_bits'  => 4096,
            "private_key_type"  => OPENSSL_KEYTYPE_RSA,
        ], $config);

        $privateKey = openssl_pkey_new($config);
        if ($privateKey === false) {
            throw new \Exception('Failed to create a key pair', 500);
        }

        if (openssl_pkey_export($privateKey, $privateKeyPEM, null, $config) === false) {
            throw new \Exception('Failed to export a key pair', 500);
        }

        $details = openssl_pkey_get_details($privateKey);
        if ($details === false) {
            throw new \Exception('Failure to obtain key information', 500);
        }

        $publicKeyPEM = $details['key'];
        return 'The key pair was successfully created';
    }

    public function encrypt($data, $cipher_algo, $passphrase = null)
    {
        $key = getenv('APP_KEY') ? getenv('APP_KEY') : 'NIX_SECRET';
        $passphrase = $passphrase ? $passphrase : $key;

        $iv = substr(base64_encode($passphrase ? $passphrase : $key), 0, 15);
        $iv = strlen($iv) < 16 ? str_pad($iv, 16, $iv) : $iv;

        return openssl_encrypt($data, $cipher_algo, $passphrase, 0, ($iv));
    }

    public function decrypt($data, $cipher_algo, $passphrase = null)
    {
        $key = getenv('APP_KEY') ? getenv('APP_KEY') : 'NIX_SECRET';
        $passphrase = $passphrase ? $passphrase : $key;

        $iv = substr(base64_encode($passphrase ? $passphrase : $key), 0, 15);
        $iv = strlen($iv) < 16 ? str_pad($iv, 16, $iv) : $iv;

        return openssl_decrypt($data, $cipher_algo, $passphrase, 0, ($iv));
    }
}
