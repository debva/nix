<?php

namespace Debva\Nix\Extension\SatuSehat;

trait Encounter
{
    public function encounterCreate($data)
    {
        $response = http()->get(
            "{$this->baseURL}/Encounter",
            ["Authorization: Bearer {$this->token}"],
            $data
        );

        return $this->response($response);
    }
}
