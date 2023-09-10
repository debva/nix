<?php

namespace Debva\Nix;

class Http
{
    public function get($url, $body = [], $headers = [])
    {
        try {
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL             => $url,
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_ENCODING        => 'gzip',
                CURLOPT_MAXREDIRS       => 10,
                CURLOPT_SSL_VERIFYPEER  => false,
                CURLOPT_TIMEOUT         => 0,
                CURLOPT_FOLLOWLOCATION  => true,
                CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST   => 'GET',
            ]);

            if (!empty($body)) curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
            if (!empty($headers)) curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($curl);
            if (curl_errno($curl)) throw new \Exception(curl_error($curl));
            curl_close($curl);

            return json_decode($response, true);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function post($url, $body = [], $headers = [])
    {
        try {
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL             => $url,
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_ENCODING        => 'gzip',
                CURLOPT_MAXREDIRS       => 10,
                CURLOPT_SSL_VERIFYPEER  => false,
                CURLOPT_TIMEOUT         => 0,
                CURLOPT_FOLLOWLOCATION  => true,
                CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST   => 'POST',
            ]);

            if (!empty($body)) curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
            if (!empty($headers)) curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($curl);
            if (curl_errno($curl)) throw new \Exception(curl_error($curl));
            curl_close($curl);

            return json_decode($response, true);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
