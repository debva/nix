<?php

namespace Debva\Nix\Extension\SatuSehat\DataType;

use Debva\Nix\Extension\SatuSehat\Base;

class CodeableConcept extends Base
{
    public function __invoke($data, $valueSet)
    {
        return array_filter([
            'coding' => $this->mapping($data, 'coding', function ($coding) use ($valueSet) {
                return $this->getDataType('Coding', $coding, $valueSet);
            }),
            'text' => $this->getResponse($data, 'text'),
        ]);
    }
}
