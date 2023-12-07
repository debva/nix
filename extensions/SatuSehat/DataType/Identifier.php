<?php

namespace Debva\Nix\Extension\SatuSehat\DataType;

use Debva\Nix\Extension\SatuSehat\Base;

class Identifier extends Base
{
    public function __invoke($data)
    {
        return array_filter([
            'system' => isset($data['system']) ? $data['system'] : null,
            'value' => isset($data['value']) ? $data['value'] : null,
            'use' => isset($data['use']) ? $this->getValueSet('IdentifierUse', $data['use']) : null
        ]);
    }
}
