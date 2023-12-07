<?php

namespace Debva\Nix\Extension\SatuSehat\ValueSet;

class ContactPointUse
{
    public function __invoke()
    {
        return [
            'home' => [
                'display'       => 'Home',
                'definition'    => 'A communication contact point at a home; attempted contacts for business purposes might intrude privacy and chances are one will contact family or other household members instead of the person one wishes to call. Typically used with urgent cases, or if no other contacts are available.'
            ],
            'work' => [
                'display'       => 'Work',
                'definition'    => 'An office contact point. First choice for business-related contacts during business hours.'
            ],
            'temp' => [
                'display'       => 'Temp',
                'definition'    => 'A temporary contact point. The period can provide more detailed information.'
            ],
            'old' => [
                'display'       => 'Old',
                'definition'    => 'This contact point is no longer in use (or was never correct, but retained for records).'
            ],
            'mobile' => [
                'display'       => 'Mobile',
                'definition'    => 'A telecommunication device that moves and stays with its owner. May have characteristics of all other use codes, suitable for urgent matters, not the first choice for routine business.'
            ]
        ];
    }
}
