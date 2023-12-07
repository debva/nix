<?php

namespace Debva\Nix\Extension\SatuSehat\DataType;

use Debva\Nix\Extension\SatuSehat\Base;

class CodeableConcept extends Base
{
    public function __invoke($data, $valueSet)
    {
        return array_filter([
            'coding' => (isset($data['coding']) && is_array($data['coding'])) ? $this->mapping($data, 'coding', function ($coding) use ($valueSet) {
                if (is_array($coding)) return $this->getDataType('Coding', $coding, $valueSet);
                return null;
            }) : null,
            'text' => isset($data['text']) ? $data['text'] : null,
        ]);
    }
}
