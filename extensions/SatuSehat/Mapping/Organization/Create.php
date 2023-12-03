<?php

namespace Debva\Nix\Extension\SatuSehat\Mapping\Organization;

class Create
{
    public function body($data)
    {
        extract($data);

        return [
            'resourceType' => 'Organization',
            'identifier' => [
                [
                    'use'       => 'official',
                    'system'    => "http://sys-ids.kemkes.go.id/organization/{$organizationID}",
                    'value'     => "{$name} {$partOfName}"
                ]
            ],
            'active' => $active,
            'type' => [
                [
                    'coding' => [
                        [
                            'system'    => 'http://terminology.hl7.org/CodeSystem/organization-type',
                            'code'      => 'dept',
                            'display'   => 'Hospital Department'
                        ]
                    ]
                ]
            ],
            'name' => $name,
            'telecom' => [
                [
                    'system'    => 'phone',
                    'value'     => $phone,
                    'use'       => 'work'
                ],
                [
                    'system'    => 'email',
                    'value'     => $email,
                    'use'       => 'work'
                ],
                [
                    'system'    => 'url',
                    'value'     => $url,
                    'use'       => 'work'
                ],
            ],
            'address' => [
                [
                    'use'           => 'work',
                    'type'          => 'both',
                    'line'          => [$line],
                    'city'          => $city,
                    'postalCode'    => $postalCode,
                    'country'       => $country,
                    'extension'     => [
                        [
                            'url'       => 'https://fhir.kemkes.go.id/r4/StructureDefinition/administrativeCode',
                            'extension' => [
                                [
                                    'url'       => 'province',
                                    'valueCode' => $provinceCode
                                ],
                                [
                                    'url'       => 'city',
                                    'valueCode' => $cityCode
                                ],
                                [
                                    'url'       => 'district',
                                    'valueCode' => $districtCode
                                ],
                                [
                                    'url'       => 'village',
                                    'valueCode' => $villageCode
                                ],
                            ]
                        ]
                    ]
                ]
            ],
            'partOf' => [
                'reference' => "Organization/{$partOfId}"
            ]
        ];
    }

    public function response($data)
    {
        $identifier = isset($data['identifier']) ? array_map(function ($identifier) {
            return [
                'system'    => $identifier['system'],
                'value'     => $identifier['value']
            ];
        }, $data['identifier']) : null;

        $address = isset($data['address']) ? array_map(function ($address) {
            return [
                'city'          => isset($address['city']) ? $address['city'] : null,
                'country'       => isset($address['country']) ? $address['country'] : null,
                'line'          => isset($address['line']) ? $address['line'] : null,
                'postalCode'    => isset($address['postalCode']) ? $address['postalCode'] : null,
                'extension'     => isset($address['extension']) ? array_map(function ($extension) {
                    return $extension['extension'];
                }, $address['extension']) : null,
            ];
        }, $data['address']) : [];

        return [
            'id'            => isset($data['id']) ? $data['id'] : null,
            'identifier'    => $identifier,
            'active'        => isset($data['active']) ? $data['active'] : null,
            'name'          => isset($data['name']) ? $data['name'] : null,
            'address'       => $address,
            'telecom'       => isset($data['telecom']) ? $data['telecom'] : null,
            'partOf'        => isset($data['partOf']['reference']) ? $data['partOf']['reference'] : null,
        ];
    }
}
