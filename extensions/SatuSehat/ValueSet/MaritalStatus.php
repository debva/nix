<?php

namespace Debva\Nix\Extension\SatuSehat\ValueSet;

class MaritalStatus
{
    public function __invoke()
    {
        return [
            'A' => [
                'system'      => 'http://terminology.hl7.org/CodeSystem/v3-MaritalStatus',
                'display'     => 'Annulled',
                'definition'  => 'Marriage contract has been declared null and to not have existed'
            ],
            'D' => [
                'system'      => 'http://terminology.hl7.org/CodeSystem/v3-MaritalStatus',
                'display'     => 'Divorced',
                'definition'  => 'Marriage contract has been declared dissolved and inactive'
            ],
            'I' => [
                'system'      => 'http://terminology.hl7.org/CodeSystem/v3-MaritalStatus',
                'display'     => 'Interlocutory',
                'definition'  => 'Subject to an Interlocutory Decree.'
            ],
            'L' => [
                'system'      => 'http://terminology.hl7.org/CodeSystem/v3-MaritalStatus',
                'display'     => 'Legally Separated',
                'definition'  => 'Legally Separated'
            ],
            'M' => [
                'system'      => 'http://terminology.hl7.org/CodeSystem/v3-MaritalStatus',
                'display'     => 'Married',
                'definition'  => 'A current marriage contract is active'
            ],
            'P' => [
                'system'      => 'http://terminology.hl7.org/CodeSystem/v3-MaritalStatus',
                'display'     => 'Polygamous',
                'definition'  => 'More than 1 current spouse'
            ],
            'S' => [
                'system'      => 'http://terminology.hl7.org/CodeSystem/v3-MaritalStatus',
                'display'     => 'Never Married',
                'definition'  => 'No marriage contract has ever been entered'
            ],
            'T' => [
                'system'      => 'http://terminology.hl7.org/CodeSystem/v3-MaritalStatus',
                'display'     => 'Domestic partner',
                'definition'  => 'Person declares that a domestic partner relationship exists.'
            ],
            'U' => [
                'system'      => 'http://terminology.hl7.org/CodeSystem/v3-MaritalStatus',
                'display'     => 'Unmarried',
                'definition'  => 'Currently not in a marriage contract.'
            ],
            'W' => [
                'system'      => 'http://terminology.hl7.org/CodeSystem/v3-MaritalStatus',
                'display'     => 'Widowed',
                'definition'  => 'The spouse has died'
            ],
            'UNK' => [
                'system'      => 'http://terminology.hl7.org/CodeSystem/v3-NullFlavor',
                'display'     => 'unknown',
                'definition'  => 'Description: A proper value is applicable, but not known. Usage Notes: This means the actual value is not known. If the only thing that is unknown is how to properly express the value in the necessary constraints (value set, datatype, etc.), then the OTH or UNC flavor should be used. No properties should be included for a datatype with this property unless: Those properties themselves directly translate to a semantic of "unknown". (E.g. a local code sent as a translation that conveys \'unknown\') Those properties further qualify the nature of what is unknown. (E.g. specifying a use code of "H" and a URL prefix of "tel:" to convey that it is the home phone number that is unknown.)'
            ],
        ];
    }
}
