<?php

namespace Debva\Nix\Extension\SatuSehat\DataType;

use Debva\Nix\Extension\SatuSehat\Base;

class Coding extends Base
{
    public function __invoke($data, $valueSet)
    {
        return array_filter([
            'system' => isset($data['system']) ? $data['system'] : null,
            'code' => isset($data['code']) ? $data['code'] : null,
            'display' => isset($data['code']) ? $this->getValueSet($valueSet, $data['code'], 'display') : null
        ]);
    }
}
