<?php

namespace Debva\Nix;

class BPJS
{
    protected $consId = '';

    protected $secretKey = '';

    protected $userKey = '';

    protected $key = '';

    protected $baseurl = '';

    protected $endpoint = [];

    public function __construct($options, $isProduction = false)
    {
        if (!$isProduction) {
            $this->endpoint['BASE_URL_VCLAIM']          = 'https://apijkn-dev.bpjs-kesehatan.go.id/vclaim-rest-dev';
            $this->endpoint['BASE_URL_ANTREAN_RS']      = 'https://apijkn-dev.bpjs-kesehatan.go.id/antreanrs_dev';
            $this->endpoint['BASE_URL_APOTEK']          = 'https://apijkn-dev.bpjs-kesehatan.go.id/apotek-rest-dev';
            $this->endpoint['BASE_URL_PCARE']           = 'https://apijkn-dev.bpjs-kesehatan.go.id/pcare-rest-dev';
            $this->endpoint['BASE_URL_I_CARE_JKN']      = 'https://apijkn-dev.bpjs-kesehatan.go.id/ihs_dev';
            $this->endpoint['BASE_URL_E_REKAM_MEDIS']   = 'https://apijkn-dev.bpjs-kesehatan.go.id/erekammedis_dev';
        }

        extract($options, EXTR_PREFIX_ALL, 'opts');

        $this->consId = $opts_consid;
        $this->secretKey = $opts_secretkey;
        $this->userKey = $opts_userkey;

        $this->baseurl = $this->endpoint[$opts_baseurl];
    }

    public function __invoke($service, $body = [])
    {
        $service = trim($service, '\/');
        $signature = $this->createSignature();

        $response = (new Http)->{empty($body) ? 'get' : 'post'}("{$this->baseurl}/{$service}", [
            "user_key: {$this->userKey}",
            "X-cons-id: {$signature['x-cons-id']}",
            "X-timestamp: {$signature['x-timestamp']}",
            "X-signature: {$signature['x-signature']}",
            (empty($body) ? 'Content-Type: application/json; charset=utf-8' : 'Content-Type: Application/x-www-form-urlencoded')
        ], $body);

        if (isset($response['response'])) {
            return $this->decrypt($response['response']);
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
        $output = openssl_decrypt(base64_decode($string), 'AES-256-CBC', hex2bin(hash('sha256', $this->key)), OPENSSL_RAW_DATA, substr(hex2bin(hash('sha256', $this->key)), 0, 16));
        return json_decode(\LZCompressor\LZString::decompressFromEncodedURIComponent($output), true);
    }
}
