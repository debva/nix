<?php

namespace Debva\Nix\Extension\SatuSehat\DataType;

use Debva\Nix\Extension\SatuSehat\Base;

class Address extends Base
{
    public function __invoke($data)
    {
        return array_filter([
            'use' => isset($data['use']) ? $this->getValueSet('AddressUse', $data['use']) : null,
            'type' => isset($data['type']) ? $this->getValueSet('AddressType', $data['type']) : null,
            'line' => isset($data['line']) ? $this->mapping($data, 'line', function ($line) {
                if (is_string($line)) return $line;
                return null;
            }) : null,
            'city' => isset($data['city']) ? $data['city'] : null,
            'district' => isset($data['district']) ? $data['district'] : null,
            'state' => isset($data['state']) ? $data['state'] : null,
            'postalCode' => isset($data['postalCode']) ? $data['postalCode'] : null,
            'country' => isset($data['country']) ? $data['country'] : null,
            'extension' => isset($data['extension']) ? $data['extension'] : null,
        ]);
    }
}
