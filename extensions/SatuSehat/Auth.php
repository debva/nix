<?php

namespace Debva\Nix\Extension\SatuSehat;

trait Auth
{
    public function auth()
    {
        $this->module = __FUNCTION__;
        return $this;
    }

    protected function authOAuth2()
    {
        $response = http()->post(
            "{$this->authURL}/accesstoken?grant_type=client_credentials",
            ['Content-Type: application/x-www-form-urlencoded'],
            "client_id={$this->clientKey}&client_secret={$this->secretKey}"
        );

        return $this->response($response);
    }
}
