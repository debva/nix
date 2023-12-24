<?php

namespace Debva\Nix\Extension\SatuSehat\Mapping\Encounter;

use Debva\Nix\Extension\SatuSehat\Base;

class Create extends Base
{
    public function body($data)
    {
        return [];
    }

    public function response($data)
    {
        return $data;
    }
}
