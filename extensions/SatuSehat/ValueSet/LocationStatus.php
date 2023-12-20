<?php

namespace Debva\Nix\Extension\SatuSehat\ValueSet;

class LocationStatus
{
    public function __invoke()
    {
        return [
            'active' => [
                'display'           => 'Active',
                'definition'        => 'The location is operational.',
                'canonical_status'  => '~active'
            ],
            'suspended' => [
                'display'           => 'Suspended',
                'definition'        => 'The location is temporarily closed.',
                'canonical_status'  => '~suspended'
            ],
            'inactive' => [
                'display'           => 'Inactive',
                'definition'        => 'The location is no longer used.',
                'canonical_status'  => '~inactive'
            ]
        ];
    }
}
