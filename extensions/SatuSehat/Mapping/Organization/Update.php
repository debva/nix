<?php

namespace Debva\Nix\Extension\SatuSehat\Mapping\Organization;

use Debva\Nix\Extension\SatuSehat\Base;

class Update extends Base
{
    protected $method;

    public function __construct($method)
    {
        if (!in_array($method, ['PUT', 'PATCH'])) {
            throw new \Exception("Invalid method {$method} for update operation!");
        }

        $this->method = $method;
    }

    public function body($data)
    {
        switch ($this->method) {
            case 'PUT':
                $IHS = $this->mapping($data, 'IHS');
                $IHSName = $this->mapping($data, 'IHSName');

                $type = $this->mapping($data, 'type');

                $active = $this->mapping($data, 'active');
                $name = $this->mapping($data, 'name');

                $phone = $this->mapping($data, 'phone');
                $email = $this->mapping($data, 'email');
                $url = $this->mapping($data, 'url');

                $line = $this->mapping($data, 'line');
                $city = $this->mapping($data, 'city');
                $district = $this->mapping($data, 'district');
                $state = $this->mapping($data, 'state');
                $postalCode = $this->mapping($data, 'postalCode');
                $country = $this->mapping($data, 'country');

                $provinceCode = $this->mapping($data, 'provinceCode');
                $cityCode = $this->mapping($data, 'cityCode');
                $districtCode = $this->mapping($data, 'districtCode');
                $villageCode = $this->mapping($data, 'villageCode');

                $partOf = $this->mapping($data, 'partOf');
                $partOfDisplay = $this->mapping($data, 'partOfDisplay');

                $data = [
                    'id' => $this->mapping($data, 'id', true),
                    'identifier' => [$this->getDataType('Identifier', $this->mapping([
                        'use' => 'official',
                        'system' => is_null($IHS) ? null : "http://sys-ids.kemkes.go.id/organization/{$IHS}",
                        'value' => is_null($name) || is_null($IHSName) ? $name : "{$name} {$IHSName}",
                    ]))],
                    'type' => $this->mapping([$this->getDataType('CodeableConcept', $this->mapping([
                        'coding' => [[
                            'system' => is_null($type) ? null : 'http://terminology.hl7.org/CodeSystem/organization-type',
                            'code' => is_null($type) ? null : $type
                        ]]
                    ]), 'OrganizationType')]),
                    'active' => is_null($active) ? null : $active,
                    'name' => is_null($name) ? null : $name,
                    'telecom' => $this->mapping(array_filter(array_merge(
                        [is_null($phone) ? [] : ['system' => 'phone', 'value' => $phone, 'use' => 'work']],
                        [is_null($email) ? [] : ['system' => 'email', 'value' => $email, 'use' => 'work']],
                        [is_null($url) ? [] : ['system' => 'url', 'value' => $url, 'use' => 'work']]
                    )), null, function ($telecom) {
                        return $this->getDataType('ContactPoint', $telecom);
                    }),
                    'address' => !empty(array_filter([$line, $city, $district, $state, $postalCode, $provinceCode, $cityCode, $districtCode, $villageCode])) ? [$this->getDataType('Address', $this->mapping([
                        'use' => 'work',
                        'line' => is_null($line) ? [] : [$line],
                        'city' => is_null($city) ? null : $city,
                        'district' => is_null($district) ? null : $district,
                        'state' => is_null($state) ? null : $state,
                        'postalCode' => is_null($postalCode) ? null : $postalCode,
                        'country' => is_null($country) ? null : $country,
                        'extension' => !empty(array_filter([$provinceCode, $cityCode, $districtCode, $villageCode])) ? [[
                            'url' => 'https://fhir.kemkes.go.id/r4/StructureDefinition/administrativeCode',
                            'extension' => $this->mapping(array_values(array_filter(array_merge(
                                [is_null($provinceCode) ? [] : ['url' => 'province', 'valueCode' => $provinceCode]],
                                [is_null($cityCode) ? [] : ['url' => 'city', 'valueCode' => $cityCode]],
                                [is_null($districtCode) ? [] : ['url' => 'district', 'valueCode' => $districtCode]],
                                [is_null($villageCode) ? [] : ['url' => 'village', 'valueCode' => $villageCode]]
                            ))))
                        ]] : []
                    ]))] : [],
                    'partOf' => $this->getDataType('Reference', $this->mapping(['reference' => $partOf, 'display' => $partOfDisplay]), 'Organization'),
                ];

                return array_merge(array_filter([
                    'resourceType'  => 'Organization',
                    'id'            => $data['id'],
                    'identifier'    => $data['identifier'],
                    'type'          => $data['type'],
                    'name'          => $data['name'],
                    'telecom'       => $data['telecom'],
                    'address'       => $data['address'],
                    'partOf'        => $data['partOf']
                ]), ['active' => $data['active']]);

            case 'PATCH':
                if (!is_array($data)) throw new \Exception("Data must be an array!");
                return $this->mapping($data, null, function ($data) {
                    if (isset($data['path'], $data['value'])) {
                        return [
                            'op'    => 'replace',
                            'path'  => $data['path'],
                            'value' => $data['value'],
                        ];
                    }
                    return null;
                });
        }
    }

    public function response($data)
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
            'resourceType'  => 'Organization',
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
}
