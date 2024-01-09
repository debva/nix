<?php

namespace Debva\Nix\Extension\SatuSehat\Mapping\Encounter;

use Debva\Nix\Extension\SatuSehat\Base;

class Create extends Base
{
    public function body($data)
    {
        $condition = $this->mapping($data, 'condition');
        $procedure = $this->mapping($data, 'procedure');
        return $data;
    }

    public function response($data)
    {
        return $data;
    }
}
