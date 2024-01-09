<?php

namespace Debva\Nix\Extension\SatuSehat\ValueSet;

class ConditionClinicalStatusCodes
{
    public function __invoke()
    {
        return [
            'active' => [
                'lvl'        => 0,
                'display'    => 'Active',
                'definition' => 'The subject is currently experiencing the symptoms of the condition or there is evidence of the condition.',
            ],
            'recurrence' => [
                'lvl'        => 1,
                'display'    => 'Recurrence',
                'definition' => 'The subject is experiencing a re-occurrence or repeating of a previously resolved condition, e.g. urinary tract infection, pancreatitis, cholangitis, conjunctivitis.',
            ],
            'relapse' => [
                'lvl'        => 1,
                'display'    => 'Relapse',
                'definition' => 'The subject is experiencing a return of a condition, or signs and symptoms after a period of improvement or remission, e.g. relapse of cancer, multiple sclerosis, rheumatoid arthritis, systemic lupus erythematosus, bipolar disorder, [psychotic relapse of] schizophrenia, etc.',
            ],
            'inactive' => [
                'lvl'        => 0,
                'display'    => 'Inactive',
                'definition' => 'The subject is no longer experiencing the symptoms of the condition or there is no longer evidence of the condition.',
            ],
            'remission' => [
                'lvl'        => 1,
                'display'    => 'Remission',
                'definition' => 'The subject is no longer experiencing the symptoms of the condition, but there is a risk of the symptoms returning.',
            ],
            'resolved' => [
                'lvl'        => 1,
                'display'    => 'Resolved',
                'definition' => 'The subject is no longer experiencing the symptoms of the condition and there is a negligible perceived risk of the symptoms returning.',
            ],
        ];
    }
}
