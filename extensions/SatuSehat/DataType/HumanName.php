<?php

namespace Debva\Nix\Extension\SatuSehat\DataType;

use Debva\Nix\Extension\SatuSehat\Base;

class HumanName extends Base
{
    public function __invoke($data)
    {
        $text = $this->getResponse($data, 'text');
        return array_filter([
            'use'   => is_null($text) ? null : $this->getValueSet('NameUse', $this->getResponse($data, 'use')),
            'text'  => $text,
        ]);
    }
}
