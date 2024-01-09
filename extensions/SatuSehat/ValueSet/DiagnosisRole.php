<?php

namespace Debva\Nix\Extension\SatuSehat\ValueSet;

class DiagnosisRole
{
    public function __invoke()
    {
        return [
            'AD' => [
                'display'    => 'Admission diagnosis',
                'definition' => 'Admission diagnosis',
            ],
            'DD' => [
                'display'    => 'Discharge diagnosis',
                'definition' => 'Discharge diagnosis',
            ],
            'CC' => [
                'display'    => 'Chief complaint',
                'definition' => 'Chief complaint',
            ],
            'CM' => [
                'display'    => 'Comorbidity diagnosis',
                'definition' => 'Comorbidity diagnosis',
            ],
            'pre-op' => [
                'display'    => 'Pre-op diagnosis',
                'definition' => 'Pre-op diagnosis',
            ],
            'post-op' => [
                'display'    => 'Post-op diagnosis',
                'definition' => 'Post-op diagnosis',
            ],
            'billing' => [
                'display'    => 'Billing',
                'definition' => 'Billing',
            ]
        ];
    }
}
