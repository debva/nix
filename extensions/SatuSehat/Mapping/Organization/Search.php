<?php

namespace Debva\Nix\Extension\SatuSehat\Mapping\Organization;

class Search
{
    protected function defaultResponse($data)
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

    public function response($data)
    {
        if (isset($data['total']) && isset($data['entry'])) {
            return [
                'total' => $data['total'],
                'entry' => array_map(function ($data) {
                    $data = $data['resource'];
                    return $this->defaultResponse($data);
                }, $data['entry'])
            ];
        }

        return $this->defaultResponse($data);
    }
}
