<?php

namespace Debva\Nix\Extension\SatuSehat\ValueSet;

class AdministrativeGender
{
    public function __invoke()
    {
        return [
            'male' => [
                'display'    => 'Male',
                'definition' => 'Male.'
            ],
            'female' => [
                'display'    => 'Female',
                'definition' => 'Female.'
            ],
            'other' => [
                'display'    => 'Other',
                'definition' => 'Other.'
            ],
            'unknown' => [
                'display'    => 'Unknown',
                'definition' => 'Unknown.'
            ]
        ];
    }
}
