<?php

namespace Debva\Nix\Extension\SatuSehat\ValueSet;

class EncounterType
{
    public function __invoke()
    {
        return [
            'ADMS' => [
                'display' => 'Annual diabetes mellitus screening',
            ],
            'BD/BM-clin' => [
                'display' => 'Bone drilling/bone marrow punction in clinic',
            ],
            'CCS60' => [
                'display' => 'Infant colon screening - 60 minutes',
            ],
            'OKI' => [
                'display' => 'Outpatient Kenacort injection',
            ],
        ];
    }
}
