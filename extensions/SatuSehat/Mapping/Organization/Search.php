<?php

namespace Debva\Nix\Extension\SatuSehat\Mapping\Organization;

use Debva\Nix\Extension\SatuSehat\Base;

class Search extends Base
{
    protected function defaultResponse($data)
    {
        $telecom = $this->getResponse($data, 'telecom');
        $telecom = is_null($telecom) ? [] : $telecom;

        $partOf = $this->getResponse($data, 'partOf.reference');
        $partOf = is_null($partOf) ? $partOf : array_filter(explode('/', $partOf));
        $partOf = is_null($partOf) ? $partOf : end($partOf);

        $address = $this->mapping($this->getResponse($data, 'address'), null, function ($address) {
            return [
                'country'       => $this->getResponse($address, 'country'),
                'city'          => $this->getResponse($address, 'city'),
                'line'          => $this->getResponse($address, 'line.0'),
                'provinceCode'  => $this->getResponse($address, 'extension.0.extension', ['url' => 'province'], 'valueCode'),
                'cityCode'      => $this->getResponse($address, 'extension.0.extension', ['url' => 'city'], 'valueCode'),
                'districtCode'  => $this->getResponse($address, 'extension.0.extension', ['url' => 'district'], 'valueCode'),
                'villageCode'   => $this->getResponse($address, 'extension.0.extension', ['url' => 'village'], 'valueCode'),
                'postalCode'    => $this->getResponse($address, 'postalCode')
            ];
        });

        return [
            'resourceType'  => $this->getResponse($data, 'resourceType'),
            'id'            => $this->getResponse($data, 'id'),
            'active'        => $this->getResponse($data, 'active'),
            'name'          => $this->getResponse($data, 'name'),
            'identifier'    => $this->getResponse($data, 'identifier.0.value'),
            'type'          => $this->getResponse($data, 'type.0.coding.0.display'),
            'telecom'       => array_column($telecom, 'value', 'system'),
            'address'       => $address,
            'partOf'        => $partOf
        ];
    }

    public function response($data)
    {
        if (isset($data['total']) && isset($data['entry'])) {
            return [
                'total' => $data['total'],
                'entry' => $this->mapping($data, 'entry', function ($data) {
                    return $this->defaultResponse($data['resource']);
                })
            ];
        }

        return $this->defaultResponse($data);
    }
}
