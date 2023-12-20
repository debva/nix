<?php

namespace Debva\Nix\Extension\SatuSehat\Mapping\Practitioner;

use Debva\Nix\Extension\SatuSehat\Base;

class Search extends Base
{
    public function defaultResponse($data)
    {
        $telecom = $this->getResponse($data, 'telecom');
        $telecom = is_null($telecom) ? [] : $telecom;

        $identifier = $this->mapping($data, 'identifier', function ($identifier) {
            $system = explode('/', isset($identifier['system']) ? $identifier['system'] : '');
            $system = end($system);

            if (!empty($system)) {
                return [
                    'system'    => $system,
                    'value'     => $this->getResponse($identifier, 'value')
                ];
            }
        });

        $qualification = $this->mapping($data, 'qualification', function ($qualification) {
            return [
                'display'   => $this->getResponse($qualification, 'code.coding.0.display'),
                'value'     => $this->getResponse($qualification, 'identifier.0.value')
            ];
        });

        $address = $this->mapping($data, 'address', function ($address) {
            return [
                'country'       => $this->getResponse($address, 'country'),
                'city'          => $this->getResponse($address, 'city'),
                'line'          => $this->getResponse($address, 'line.0'),
                'provinceCode'  => $this->getResponse($address, 'extension.0.extension', ['url' => 'province'], 'valueCode'),
                'cityCode'      => $this->getResponse($address, 'extension.0.extension', ['url' => 'city'], 'valueCode'),
                'districtCode'  => $this->getResponse($address, 'extension.0.extension', ['url' => 'district'], 'valueCode'),
                'villageCode'   => $this->getResponse($address, 'extension.0.extension', ['url' => 'village'], 'valueCode'),
                'rw'            => $this->getResponse($address, 'extension.0.extension', ['url' => 'rw'], 'valueCode'),
                'rt'            => $this->getResponse($address, 'extension.0.extension', ['url' => 'rt'], 'valueCode'),
                'postalCode'    => $this->getResponse($address, 'postalCode')
            ];
        });

        return [
            'resourceType'  => $this->getResponse($data, 'resourceType'),
            'id'            => $this->getResponse($data, 'id'),
            'identifier'    => $identifier,
            'gender'        => $this->getResponse($data, 'gender'),
            'birthDate'     => $this->getResponse($data, 'birthDate'),
            'name'          => $this->getResponse($data, 'name', ['use' => 'official'], 'text'),
            'telecom'       => array_column($telecom, 'value', 'system'),
            'qualification' => $qualification,
            'address'       => $address,
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
