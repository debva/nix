<?php

namespace Debva\Nix\Extension\SatuSehat\Mapping\Practitioner;

use Debva\Nix\Extension\SatuSehat\Base;

class Search extends Base
{
    public function defaultResponse($data)
    {
        $telecom = $this->getResponse($data, 'telecom');
        $telecom = is_null($telecom) ? [] : $telecom;

        $identifier = $this->mapping($this->getResponse($data, 'identifier'), null, function ($identifier) {
            return [
                'system'    => $this->getResponse($identifier, 'system'),
                'value'     => $this->getResponse($identifier, 'value')
            ];
        });

        $qualification = $this->mapping($this->getResponse($data, 'qualification'), null, function ($qualification) {
            return $this->getResponse($qualification, 'identifier');
        });

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
            'identifier'    => $identifier,
            'gender'        => $this->getResponse($data, 'gender'),
            'birthDate'     => $this->getResponse($data, 'birthDate'),
            'name'          => $this->getResponse($data, 'name.0.text'),
            'telecom'       => array_column($telecom, 'value', 'system'),
            'qualification' => is_array($qualification) ? array_merge(...$qualification) : $qualification,
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
