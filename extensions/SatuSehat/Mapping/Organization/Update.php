<?php

namespace Debva\Nix\Extension\SatuSehat\Mapping\Organization;

class Update
{
    protected $method;

    public function __construct()
    {
        $method = isMethod();

        if (!in_array($method, ['PUT', 'PATCH'])) {
            throw new \Exception("Invalid method {$method} for update operation!");
        }

        $this->method = $method;
    }

    public function body($data)
    {
        switch ($this->method) {
            case 'PUT':
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

            case 'PATCH':
                return;
        }
    }

    public function response($data)
    {
        return $data;
    }
}
