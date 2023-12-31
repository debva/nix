<?php

namespace Debva\Nix\Extension\SatuSehat\ValueSet;

class IdentifierUse
{
    public function __invoke()
    {
        return [
            'usual' => [
                'display'       => 'Usual',
                'definition'    => 'The identifier recommended for display and use in real-world interactions.'
            ],
            'official' => [
                'display'       => 'Official',
                'definition'    => 'The identifier considered to be most trusted for the identification of this item. Sometimes also known as "primary" and "main". The determination of "official" is subjective and implementation guides often provide additional guidelines for use.'
            ],
            'temp' => [
                'display'       => 'Temp',
                'definition'    => 'A temporary identifier.'
            ],
            'secondary' => [
                'display'       => 'Secondary',
                'definition'    => 'An identifier that was assigned in secondary use - it serves to identify the object in a relative context, but cannot be consistently assigned to the same object again in a different context.'
            ],
            'old' => [
                'display'       => 'Old',
                'definition'    => 'The identifier is no longer considered valid, but may be relevant for search purposes. E.g. Changes to identifier schemes, account merges, etc.'
            ]
        ];
    }
}
