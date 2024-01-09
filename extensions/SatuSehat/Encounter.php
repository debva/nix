<?php

namespace Debva\Nix\Extension\SatuSehat;

use Debva\Nix\Extension\SatuSehat\Mapping\Encounter\Create;

trait Encounter
{
    public function encounter()
    {
        $this->module = __FUNCTION__;
        return $this;
    }

    public function encounterCreate($data)
    {
        $this->mapping = new Create;

        print(response($this->mapping->body($data)));
        exit;

        // $response = http()->post(
        //     "{$this->baseURL}/Encounter",
        //     $this->headers,
        //     $this->mapping->body($data)
        // );

        // return $this->response($response);
    }
}
