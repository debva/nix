<?php

namespace Debva\Nix\Extension\SatuSehat\Mapping\Location;

use Debva\Nix\Extension\SatuSehat\Base;

class Search extends Base
{
    protected function defaultResponse($data)
    {
        $telecom = $this->getResponse($data, 'telecom');
        $telecom = is_null($telecom) ? [] : $telecom;

        $managingOrganization = array_filter(explode('/', $this->getResponse($data, 'managingOrganization.reference')));
        $partOf = array_filter(explode('/', $this->getResponse($data, 'partOf.reference')));

        $address = [
            'country'       => $this->getResponse($data, 'address.country'),
            'city'          => $this->getResponse($data, 'address.city'),
            'line'          => $this->getResponse($data, 'address.line.0'),
            'provinceCode'  => $this->getResponse($data, 'address.extension.0.extension', ['url' => 'province'], 'valueCode'),
            'cityCode'      => $this->getResponse($data, 'address.extension.0.extension', ['url' => 'city'], 'valueCode'),
            'districtCode'  => $this->getResponse($data, 'address.extension.0.extension', ['url' => 'district'], 'valueCode'),
            'villageCode'   => $this->getResponse($data, 'address.extension.0.extension', ['url' => 'village'], 'valueCode'),
            'postalCode'    => $this->getResponse($data, 'address.postalCode'),
        ];

        return [
            'resourceType'          => 'Location',
            'id'                    => $this->getResponse($data, 'id'),
            'status'                => $this->getResponse($data, 'status'),
            'name'                  => $this->getResponse($data, 'name'),
            'description'           => $this->getResponse($data, 'description'),
            'mode'                  => $this->getResponse($data, 'mode'),
            'identifier'            => $this->getResponse($data, 'identifier.0.value'),
            'physicalType'          => $this->getResponse($data, 'physicalType.coding.0.display'),
            'telecom'               => array_column($telecom, 'value', 'system'),
            'address'               => $address,
            'position'              => $this->getResponse($data, 'position'),
            'managingOrganization'  => end($managingOrganization),
            'partOf'                => end($partOf),
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
