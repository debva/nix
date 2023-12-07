<?php

namespace Debva\Nix\Extension\SatuSehat\ValueSet;

class AddressType
{
    public function __invoke()
    {
        return [
            'postal' => [
                'display'       => 'Postal',
                'definition'    => 'Mailing addresses - PO Boxes and care-of addresses.',
            ],
            'physical' => [
                'display'       => 'Physical',
                'definition'    => 'A physical address that can be visited.',
            ],
            'both' => [
                'display'       => 'Postal & Physical',
                'definition'    => 'An address that is both physical and postal.',
            ],
        ];
    }
}
