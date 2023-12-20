<?php

namespace Debva\Nix\Extension\SatuSehat\Mapping\Patient;

use Debva\Nix\Extension\SatuSehat\Base;

class Search extends Base
{
    protected function defaultResponse($data)
    {
        $telecom = $this->getResponse($data, 'telecom');
        $telecom = is_null($telecom) ? [] : $telecom;

        $maritalStatus = $this->mapping($data, 'maritalStatus.coding', function ($coding) {
            return isset($coding['display']) ? $coding['display'] : $coding['code'];
        });

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
        $identifier = is_null($identifier) ? [] : $identifier;

        $extension = $this->mapping($data, 'extension', function ($extension) {
            $url = explode('/', isset($extension['url']) ? $extension['url'] : '');
            $url = end($url);

            if (!empty($url)) {
                return [
                    'url'       => $url,
                    'valueCode' => $this->getResponse($extension, 'valueCode')
                ];
            }
        });
        $extension = is_null($extension) ? [] : $extension;

        $communication = $this->mapping($data, 'coding', function ($communication) {
            return [
                'language'  => $this->getResponse($communication, 'language.text'),
                'preferred' => $this->getResponse($communication, 'preferred')
            ];
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
                'rw'            => $this->getResponse($address, 'extension.0.extension', ['url' => 'rw'], 'valueCode'),
                'rt'            => $this->getResponse($address, 'extension.0.extension', ['url' => 'rt'], 'valueCode'),
                'postalCode'    => $this->getResponse($address, 'postalCode')
            ];
        });

        return [
            'resourceType'          => $this->getResponse($data, 'resourceType'),
            'id'                    => $this->getResponse($data, 'id'),
            'active'                => $this->getResponse($data, 'active'),
            'name'                  => $this->getResponse($data, 'name', ['use' => 'official'], 'text'),
            'gender'                => $this->getResponse($data, 'gender'),
            'birthDate'             => $this->getResponse($data, 'birthDate'),
            'maritalStatus'         => $maritalStatus,
            'multipleBirthBoolean'  => $this->getResponse($data, 'multipleBirthBoolean'),
            'multipleBirthInteger'  => $this->getResponse($data, 'multipleBirthInteger'),
            'deceasedBoolean'       => $this->getResponse($data, 'deceasedBoolean'),
            'deceasedDateTime'      => $this->getResponse($data, 'deceasedDateTime'),
            'identifier'            => array_column($identifier, 'value', 'system'),
            'telecom'               => array_column($telecom, 'value', 'system'),
            'extension'             => array_column($extension, 'valueCode', 'url'),
            'communication'         => $communication,
            'address'               => $address
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
