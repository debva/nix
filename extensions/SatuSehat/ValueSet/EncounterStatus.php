<?php

namespace Debva\Nix\Extension\SatuSehat\ValueSet;

class EncounterStatus
{
    public function __invoke()
    {
        return [
            'planned' => [
                'display'           => 'Planned',
                'definition'        => 'The Encounter has not yet started.',
                'canonical_status'  => '~planned',
            ],
            'arrived' => [
                'display'           => 'Arrived',
                'definition'        => 'The Patient is present for the encounter, however is not currently meeting with a practitioner.',
                'canonical_status'  => '~arrived',
            ],
            'triaged' => [
                'display'           => 'Triaged',
                'definition'        => 'The patient has been assessed for the priority of their treatment based on the severity of their condition.',
                'canonical_status'  => '~accepted',
            ],
            'in-progress' => [
                'display'           => 'In Progress',
                'definition'        => 'The Encounter has begun and the patient is present / the practitioner and the patient are meeting.',
                'canonical_status'  => '~active',
            ],
            'onleave' => [
                'display'           => 'On Leave',
                'definition'        => 'The Encounter has begun, but the patient is temporarily on leave.',
                'canonical_status'  => '~suspended',
            ],
            'finished' => [
                'display'           => 'Finished',
                'definition'        => 'The Encounter has ended.',
                'canonical_status'  => '~complete',
            ],
            'cancelled' => [
                'display'           => 'Cancelled',
                'definition'        => 'The Encounter has ended before it has begun.',
                'canonical_status'  => '~abandoned',
            ],
            'entered-in-error' => [
                'display'           => 'Entered in Error',
                'definition'        => 'This instance should not have been part of this patient\'s medical record.',
                'canonical_status'  => '~error',
            ],
            'unknown' => [
                'display'           => 'Unknown',
                'definition'        => 'The encounter status is unknown. Note that "unknown" is a value of last resort and every attempt should be made to provide a meaningful value other than "unknown".',
                'canonical_status'  => '~unknown',
            ],
        ];
    }
}
