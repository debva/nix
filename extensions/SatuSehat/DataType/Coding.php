<?php

namespace Debva\Nix\Extension\SatuSehat\DataType;

use Debva\Nix\Extension\SatuSehat\Base;

class Coding extends Base
{
    public function __invoke($data, $valueSet)
    {
        $system = $this->getResponse($data, 'system');
        $code = $this->getResponse($data, 'code');
        return array_filter([
            'system' => is_null($system) ? $this->getValueSet($valueSet, $code, 'system') : $system,
            'code' => $code,
            'display' => $this->getValueSet($valueSet, $code, 'display'),
        ]);
    }
}
