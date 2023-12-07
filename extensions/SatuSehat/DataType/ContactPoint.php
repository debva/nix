<?php

namespace Debva\Nix\Extension\SatuSehat\DataType;

use Debva\Nix\Extension\SatuSehat\Base;

class ContactPoint extends Base
{
    public function __invoke($data)
    {
        return array_filter([
            'system' => isset($data['system']) ? $this->getValueSet('ContactPointSystem', $data['system']) : null,
            'value' => isset($data['value']) ? $data['value'] : null,
            'use' => isset($data['use']) ? $this->getValueSet('ContactPointUse', $data['use']) : null
        ]);
    }
}
