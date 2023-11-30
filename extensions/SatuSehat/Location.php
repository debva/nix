<?php

namespace Debva\Nix\Extension\SatuSehat;

trait Location
{
    public function locationByID($id)
    {
        $response = http()->get(
            "{$this->baseURL}/Location/{$id}",
            ["Authorization: Bearer {$this->token}"]
        );
        return $this->response($response);
    }
}
