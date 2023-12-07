<?php

namespace Debva\Nix\Extension\SatuSehat\Mapping\Location;

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
                $IHSNumber = $this->mapping($data, 'IHSNumber');
                $code = $this->mapping($data, 'code');

                $status = $this->mapping($data, 'status');
                $name = $this->mapping($data, 'name');
                $description = $this->mapping($data, 'description');
                $mode = $this->mapping($data, 'mode');

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
                $rtCode = $this->mapping($data, 'rtCode');
                $rwCode = $this->mapping($data, 'rwCode');

                $physicalType = $this->mapping($data, 'physicalType');
                $longitude = $this->mapping($data, 'longitude', true);
                $latitude = $this->mapping($data, 'latitude', true);
                $managingOrganization = $this->mapping($data, 'managingOrganization');
                $partOf = $this->mapping($data, 'partOf');
                $partOfDisplay = $this->mapping($data, 'partOfDisplay');

                $data = [
                    'id' => $this->mapping($data, 'id', true),
                    'identifier' => [$this->getDataType('Identifier', $this->mapping([
                        'system' => is_null($IHSNumber) ? null : "http://sys-ids.kemkes.go.id/location/{$IHSNumber}",
                        'value' => is_null($code) ? null : $code,
                    ]))],
                    'status' => is_null($status) ? null : $this->getValueSet('LocationStatus', $status),
                    'name' => is_null($name) ? null : $name,
                    'description' => is_null($description) ? null : $description,
                    'mode' => is_null($mode) ? null : $this->getValueSet('LocationMode', $mode),
                    'telecom' => $this->mapping(array_filter(array_merge(
                        [is_null($phone) ? [] : ['system' => 'phone', 'value' => $phone, 'use' => 'work']],
                        [is_null($email) ? [] : ['system' => 'email', 'value' => $email, 'use' => 'work']],
                        [is_null($url) ? [] : ['system' => 'url', 'value' => $url, 'use' => 'work']]
                    )), null, function ($telecom) {
                        return $this->getDataType('ContactPoint', $telecom);
                    }),
                    'address' => $this->getDataType('Address', $this->mapping([
                        'use' => 'work',
                        'line' => is_null($line) ? [] : [$line],
                        'city' => is_null($city) ? null : $city,
                        'district' => is_null($district) ? null : $district,
                        'state' => is_null($state) ? null : $state,
                        'postalCode' => is_null($postalCode) ? null : $postalCode,
                        'country' => is_null($country) ? null : $country,
                        'extension' => !empty(array_filter([$provinceCode, $cityCode, $districtCode, $villageCode, $rtCode, $rwCode])) ? [[
                            'url' => 'https://fhir.kemkes.go.id/r4/StructureDefinition/administrativeCode',
                            'extension' => $this->mapping(array_values(array_filter(array_merge(
                                [is_null($provinceCode) ? [] : ['url' => 'province', 'valueCode' => $provinceCode]],
                                [is_null($cityCode) ? [] : ['url' => 'city', 'valueCode' => $cityCode]],
                                [is_null($districtCode) ? [] : ['url' => 'district', 'valueCode' => $districtCode]],
                                [is_null($villageCode) ? [] : ['url' => 'village', 'valueCode' => $villageCode]],
                                [is_null($rtCode) ? [] : ['url' => 'rt', 'valueCode' => $rtCode]],
                                [is_null($rwCode) ? [] : ['url' => 'rw', 'valueCode' => $rwCode]]
                            ))))
                        ]] : []
                    ])),
                    'physicalType' => $this->getDataType('CodeableConcept', $this->mapping([
                        'coding' => [[
                            'system' => is_null($physicalType) ? null : 'http://terminology.hl7.org/CodeSystem/location-physical-type',
                            'code' => is_null($physicalType) ? null : $physicalType
                        ]]
                    ]), 'LocationType'),
                    'position' => [
                        'longitude' => $longitude,
                        'latitude' => $latitude,
                        'altitude' => 0,
                    ],
                    'managingOrganization' => $this->getDataType('Reference', $this->mapping(['reference' => $managingOrganization]), 'Organization'),
                    'partOf' => $this->getDataType('Reference', $this->mapping(['reference' => $partOf, 'display' => $partOfDisplay]), 'Location'),
                ];

                return array_filter([
                    'resourceType'          => 'Location',
                    'id'                    => $data['id'],
                    'identifier'            => $data['identifier'],
                    'status'                => $data['status'],
                    'name'                  => $data['name'],
                    'description'           => $data['description'],
                    'mode'                  => $data['mode'],
                    'telecom'               => $data['telecom'],
                    'address'               => $data['address'],
                    'physicalType'          => $data['physicalType'],
                    'position'              => $data['position'],
                    'managingOrganization'  => $data['managingOrganization'],
                    'partOf'                => $data['partOf']
                ]);

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
}
