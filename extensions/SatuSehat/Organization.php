<?php

namespace Debva\Nix\Extension\SatuSehat;

trait Organization
{
    public function organization()
    {
        $this->module = __FUNCTION__;
        return $this;
    }

    protected function organizationCreate($data)
    {
        extract($data);

        $data = [
            'resourceType' => 'Organization',
            'identifier' => [
                [
                    'use'       => 'official',
                    'system'    => "http://sys-ids.kemkes.go.id/organization/{$this->organizationID}",
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

        $response = http()->post("{$this->baseURL}/Organization", $this->headers, $data);

        return $this->response($response);
    }
}
