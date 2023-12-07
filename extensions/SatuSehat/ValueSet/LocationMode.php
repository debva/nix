<?php

namespace Debva\Nix\Extension\SatuSehat\ValueSet;

class LocationMode
{
    public function __invoke()
    {
        return [
            'instance' => [
                'display'       => 'Instance',
                'Definition'    => 'The Location resource represents a specific instance of a location (e.g. Operating Theatre 1A).'
            ],
            'kind' => [
                'display'       => 'Kind',
                'Definition'    => 'The Location represents a class of locations (e.g. Any Operating Theatre) although this class of locations could be constrained within a specific boundary (such as organization, or parent location, address etc.).'
            ]
        ];
    }
}
