<?php

namespace Debva\Nix\Extension\SatuSehat\ValueSet;

class AddressUse
{
    public function __invoke()
    {
        return [
            'home' => [
                'display'       => 'Home',
                'definition'    => 'A communication address at a home.',
            ],
            'work' => [
                'display'       => 'Work',
                'definition'    => 'An office address. First choice for business-related contacts during business hours.',
            ],
            'temp' => [
                'display'       => 'Temporary',
                'definition'    => 'A temporary address. The period can provide more detailed information.',
            ],
            'old' => [
                'display'       => 'Old / Incorrect',
                'definition'    => 'This address is no longer in use (or was never correct but retained for records).',
            ],
            'billing' => [
                'display'       => 'Billing',
                'definition'    => 'An address to be used to send bills, invoices, receipts etc.',
            ],
        ];
    }
}
