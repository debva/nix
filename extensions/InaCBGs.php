<?php

namespace Debva\Nix\Extension;

class InaCBGs
{
    protected $url = '';

    protected $key = '';

    protected $errors = [
        'E2001' => 'Method tidak ada',
        'E2002' => 'Klaim belum final',
        'E2003' => 'Nomor SEP terduplikasi',
        'E2004' => 'Nomor SEP tidak ditemukan',
        'E2005' => 'NIK Coder masih kosong',
        'E2006' => 'NIK Coder tidak ditemukan',
        'E2007' => 'Duplikasi nomor SEP',
        'E2008' => 'Nomor RM tidak ditemukan',
        'E2009' => 'Klaim sudah final',
        'E2010' => 'Nomor SEP baru sudah terpakai',
        'E2011' => 'Klaim tidak bisa diubah/edit',
        'E2012' => 'Tanggal Pulang mendahului Tanggal Masuk',
        'E2013' => 'Lama rawat intensif melebihi total lama rawat',
        'E2014' => 'Kode tarif invalid',
        'E2015' => 'Kode RS belum disetup',
        'E2016' => 'CBG Code invalid, tidak bisa final',
        'E2017' => 'Klaim belum digrouping',
        'E2018' => 'Klaim masih belum final',
        'E2019' => 'Tanggal invalid.',
        'E2020' => 'Response web service SEP kosong',
        'E2021' => 'Gagal men-decode JSON - Maximum stack depth exceeded',
        'E2022' => 'Gagal men-decode JSON - Underflow or the modes mismatch',
        'E2023' => 'Gagal men-decode JSON - Unexpected control character found',
        'E2024' => 'Gagal men-decode JSON - Syntax error, malformed JSON',
        'E2025' => 'Gagal men-decode JSON - Malformed UTF-8 characters',
        'E2026' => 'Gagal men-decode JSON - Unknown error',
        'E2027' => 'Rumah sakit belum terdaftar',
        'E2028' => 'Jenis rawat invalid',
        'E2029' => 'Koneksi gagal',
        'E2030' => 'Parameter tidak lengkap',
        'E2031' => 'Key Mismatch',
        'E2032' => 'Parameter kenaikan kelas tersebut tidak diperbolehkan',
        'E2033' => 'Parameter payor_id tidak boleh kosong',
        'E2034' => 'Nomor klaim tidak ditemukan',
        'E2035' => 'Lama hari episode ruang rawat tidak sama dengan total lama rawat',
        'E2036' => 'Tipe file tidak diterima',
        'E2037' => 'Gagal upload',
        'E2038' => 'Gagal hapus, klaim sudah diproses',
        'E2039' => 'Gagal edit ulang, klaim sudah dikirim',
        'E2040' => 'Gagal final. Belum ada berkas yang diunggah.',
        'E2041' => 'Gagal final. Ada berkas yang masih gagal diunggah.',
        'E2042' => 'Menyatakan covid19_cc_ind = 1 tanpa diagnosa sekunder.',
        'E2043' => 'Nomor Klaim sudah terpakai.',
        'E2044' => 'Gagal upload. Error ketika memindahkan berkas.',
        'E2045' => 'Gagal upload. Ukuran file melebihi batas maksimal.',
        'E2046' => 'Nilai parameter covid19_status_cd tidak berlaku.',
        'E2047' => 'Gagal mendapatkan status klaim.',
        'E2048' => 'Tanggal masuk tidak berlaku untuk Jaminan KIPI.',
        'E2049' => 'Usia 7 hari ke atas tidak berlaku untuk Jaminan Bayi Baru Lahir.',
        'E2050' => 'Tanggal masuk tidak berlaku untuk Jaminan Perpanjangan Masa Rawat.',
        'E2051' => 'Parameter payor_id kosong atau invalid.',
        'E2052' => 'Parameter nomor_kartu_t invalid.',
        'E2053' => 'Nomor klaim ibu invalid.',
        'E2054' => 'Parameter bayi_lahir_status_cd invalid.',
        'E2055' => 'Kode jenis ruangan pada parameter episodes invalid.',
        'E2056' => 'Parameter akses_naat invalid.',
        'E2057' => 'Nilai terapi_konvalesen pada non ranap atau non terkonfirmasi COVID-19.',
        'E2058' => 'Parameter file_class invalid.',
        'E2059' => 'Parameter covid19_no_sep invalid.',
        'E2060' => 'Diagnosa Primer untuk COVID-19 tidak sesuai ketentuan.',
        'E2061' => 'Isolasi mandiri di RS pada rawat IGD.',
        'E2062' => 'Lama rawat kelas upgrade lebih lama dari total lama rawat.',
        'E2063' => 'Gagal final. Hasil INA Grouper tidak valid.',
        'E2064' => 'upgrade_class_payor masih kosong atau tidak sesuai ketentuan.',
        'E2065' => 'Kelas 3 tidak diperkenankan naik kelas.',
        'E2066' => 'Gagal final. Pasien dengan TB belum ada validasi SITB.',
        'E2099' => 'Error tidak diketahui.'
    ];

    public function __construct($url, $key)
    {
        $this->url = $url;

        $this->key = $key;
    }

    public function __invoke($payload, $withErrorCheck = false)
    {
        $payload = $this->encrypt($payload);

        $response = http()->post($this->url, [], $payload);

        if (is_null($response)) {
            return false;
        }

        $response = $this->decrypt($response);

        $error = (is_array($response) && isset($response['metadata']['error_no'])) ? $response['metadata']['error_no'] : false;

        if (is_object($response) || ($withErrorCheck && is_string($error) && in_array($error, array_keys($this->errors)))) {
            throw new \Exception($error ? $this->errors[$error] : 'Unable to connect to InaCBGs server!', 500);
        }

        return $response;
    }

    public function throwError($errorCode)
    {
        if (is_null($errorCode) || !is_string($errorCode) || (is_string($errorCode) && empty(trim($errorCode)))) {
            return true;
        }

        $message = isset($this->errors[$errorCode]) ? $this->errors[$errorCode] : "Unknown error code: {$errorCode}";
        throw new \Exception($message, 400);
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
