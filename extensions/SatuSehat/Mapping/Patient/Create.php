<?php

namespace Debva\Nix\Extension\SatuSehat\Mapping\Patient;

use Debva\Nix\Extension\SatuSehat\Base;

class Create extends Base
{
    public function body($data)
    {
        $identifier = $this->mapping($data, 'identifier', true);
        $identifierValue = $this->mapping($data, 'identifierValue', true);

        $active = $this->mapping($data, 'active', true);
        $name = $this->mapping($data, 'name', true);
        $gender = $this->mapping($data, 'gender', true);
        $birthDate = $this->mapping($data, 'birthDate', true);
        $hp = $this->mapping($data, 'hp');

        $multipleBirthBoolean = $this->mapping($data, 'multipleBirthBoolean', true);
        $multipleBirthInteger = $this->mapping($data, 'multipleBirthInteger', true);

        $deceasedBoolean = $this->mapping($data, 'deceasedBoolean');
        $deceasedDateTime = $this->mapping($data, 'deceasedDateTime');

        $languageCode = $this->mapping($data, 'languageCode', true);
        $language = $this->mapping($data, 'language', true);

        $data = [
            'identifier' => [$this->getDataType('Identifier', [
                'use'       => 'official',
                'system'    => "https://fhir.kemkes.go.id/id/{$identifier}",
                'value'     => $identifierValue
            ])],
            'active' => $active,
            'name' => [$this->getDataType('HumanName', [
                'use'   => 'official',
                'text'  => $name
            ])],
            'gender' => $this->getValueSet('AdministrativeGender', $gender),
            'telecom' => [$this->getDataType('ContactPoint', [
                'use'       => 'mobile',
                'system'    => 'phone',
                'value'     => $hp
            ])],
            'communication' => [[
                'language' => $this->getDataType('CodeableConcept', [
                    'coding'    => [[
                        'code' => $languageCode
                    ]],
                    'text' => $language
                ], 'Languages'),
                'preferred' => true
            ]]
        ];

        return array_filter([
            'resourceType' => 'Patient',
            'identifier' => $this->getResponse($data, 'identifier'),
            'active' => $this->getResponse($data, 'active'),
            'name' => $this->getResponse($data, 'name'),
            'gender' => $this->getResponse($data, 'gender'),
            'birthDate' => $birthDate,
            'telecom' => $this->getResponse($data, 'telecom'),
            'multipleBirthBoolean' => $multipleBirthBoolean,
            'multipleBirthInteger' => $multipleBirthInteger,
            'deceasedBoolean' => $deceasedBoolean,
            'deceasedDateTime' => $deceasedDateTime,
            'communication' => $this->getResponse($data, 'communication')
        ], function ($data) {
            return !is_null($data);
        });
    }

    public function response($data)
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
}
