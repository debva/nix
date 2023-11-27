<?php

namespace Debva\Nix\Extension;

class BPJS
{
    protected $consId = '';

    protected $secretKey = '';

    protected $userKey = '';

    protected $key = '';

    protected $baseurl = '';

    public function __construct($options, $isProduction = true)
    {
        $this->defineService($isProduction);

        extract($options, EXTR_PREFIX_ALL, 'opts');

        $this->consId = $opts_consid;
        $this->secretKey = $opts_secretkey;
        $this->userKey = $opts_userkey;
    }

    public function __invoke($service, $body = [])
    {
        $service = trim($service, '\/');
        $signature = $this->createSignature();

        $response = http()->{empty($body) ? 'get' : 'post'}("{$this->baseurl}/{$service}", [
            "user_key: {$this->userKey}",
            "X-cons-id: {$signature['x-cons-id']}",
            "X-timestamp: {$signature['x-timestamp']}",
            "X-signature: {$signature['x-signature']}",
            (empty($body)
                ? 'Content-Type: application/json; charset=utf-8'
                : 'Content-Type: Application/x-www-form-urlencoded')
        ], $body);

        if (array_key_exists('response', $response)) {
            $response = array_merge($response, ['response' => $this->decrypt($response['response'])]);
        }

        return $response;
    }

    protected function defineService($isProduction)
    {
        if (!$isProduction) {
            if (!defined('EXT_BPJS_VCLAIM')) {
                define('EXT_BPJS_VCLAIM', 'https://apijkn-dev.bpjs-kesehatan.go.id/vclaim-rest-dev');
            }
            if (!defined('EXT_BPJS_ANTREAN_RS')) {
                define('EXT_BPJS_ANTREAN_RS', 'https://apijkn-dev.bpjs-kesehatan.go.id/antreanrs_dev');
            }
            if (!defined('EXT_BPJS_APOTEK')) {
                define('EXT_BPJS_APOTEK', 'https://apijkn-dev.bpjs-kesehatan.go.id/apotek-rest-dev');
            }
            if (!defined('EXT_BPJS_PCARE')) {
                define('EXT_BPJS_PCARE', 'https://apijkn-dev.bpjs-kesehatan.go.id/pcare-rest-dev');
            }
            if (!defined('EXT_BPJS_I_CARE_JKN')) {
                define('EXT_BPJS_I_CARE_JKN', 'https://apijkn-dev.bpjs-kesehatan.go.id/ihs_dev');
            }
            if (!defined('EXT_BPJS_E_REKAM_MEDIS')) {
                define('EXT_BPJS_E_REKAM_MEDIS', 'https://apijkn-dev.bpjs-kesehatan.go.id/erekammedis_dev');
            }
        } else {
            if (!defined('EXT_BPJS_VCLAIM')) {
                define('EXT_BPJS_VCLAIM', 'https://apijkn.bpjs-kesehatan.go.id/vclaim-rest');
            }
            if (!defined('EXT_BPJS_ANTREAN_RS')) {
                define('EXT_BPJS_ANTREAN_RS', 'https://apijkn.bpjs-kesehatan.go.id/antreanrs');
            }
            if (!defined('EXT_BPJS_APOTEK')) {
                define('EXT_BPJS_APOTEK', 'https://apijkn.bpjs-kesehatan.go.id/apotek-rest');
            }
            if (!defined('EXT_BPJS_PCARE')) {
                define('EXT_BPJS_PCARE', 'https://apijkn.bpjs-kesehatan.go.id/pcare-rest');
            }
            if (!defined('EXT_BPJS_I_CARE_JKN')) {
                define('EXT_BPJS_I_CARE_JKN', 'https://apijkn.bpjs-kesehatan.go.id/ihs');
            }
            if (!defined('EXT_BPJS_E_REKAM_MEDIS')) {
                define('EXT_BPJS_E_REKAM_MEDIS', 'https://apijkn.bpjs-kesehatan.go.id/erekammedis');
            }
        }
    }

    protected function createSignature()
    {
        date_default_timezone_set('UTC');
        $timestamp = strval(time() - strtotime('1970-01-01 00:00:00'));
        $signature = base64_encode(hash_hmac('sha256', "{$this->consId}&{$timestamp}", $this->secretKey, true));
        $this->key = "{$this->consId}{$this->secretKey}{$timestamp}";

        return [
            "x-cons-id"     => $this->consId,
            "x-timestamp"   => $timestamp,
            "x-signature"   => $signature,
        ];
    }

    protected function decrypt($string)
    {
        $output = openssl_decrypt(
            base64_decode($string),
            'AES-256-CBC',
            hex2bin(hash('sha256', $this->key)),
            OPENSSL_RAW_DATA,
            substr(hex2bin(hash('sha256', $this->key)), 0, 16)
        );

        return json_decode(\LZCompressor\LZString::decompressFromEncodedURIComponent($output), true);
    }

    public function setBaseURL($url)
    {
        $this->baseurl = $url;
        return $this;
    }
}
