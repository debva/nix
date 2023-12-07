<?php

namespace Debva\Nix\Extension\SatuSehat\ValueSet;

class LocationStatus
{
    public function __invoke()
    {
        return [
            'active' => [
                'Display'           => 'Active',
                'Definition'        => 'The location is operational.',
                'Canonical Status'  => '~active'
            ],
            'suspended' => [
                'Display'           => 'Suspended',
                'Definition'        => 'The location is temporarily closed.',
                'Canonical Status'  => '~suspended'
            ],
            'inactive' => [
                'Display'           => 'Inactive',
                'Definition'        => 'The location is no longer used.',
                'Canonical Status'  => '~inactive'
            ]
        ];
    }
}
