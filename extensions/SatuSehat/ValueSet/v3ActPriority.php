<?php

namespace Debva\Nix\Extension\SatuSehat\ValueSet;

class v3ActPriority
{
    public function __invoke()
    {
        return [
            'A' => [
                'lvl'           => '0',
                'display'       => 'ASAP',
                'definition'    => 'As soon as possible, next highest priority after stat.'
            ],
            'CR' => [
                'lvl'           => '0',
                'display'       => 'callback results',
                'definition'    => 'Filler should contact the placer as soon as results are available, even for preliminary results. (Was \"C\" in HL7 version 2.3\'s reporting priority.)'
            ],
            'CS' => [
                'lvl'           => '0',
                'display'       => 'callback for scheduling',
                'definition'    => 'Filler should contact the placer (or target) to schedule the service. (Was \"C\" in HL7 version 2.3\'s TQ-priority component.)'
            ],
            'CSP' => [
                'lvl'           => '1',
                'display'       => 'callback placer for scheduling',
                'definition'    => 'Filler should contact the placer to schedule the service. (Was \"C\" in HL7 version 2.3\'s TQ-priority component.)'
            ],
            'CSR' => [
                'lvl'           => '1',
                'display'       => 'contact recipient for scheduling',
                'definition'    => 'Filler should contact the service recipient (target) to schedule the service. (Was \"C\" in HL7 version 2.3\'s TQ-priority component.)'
            ],
            'EL' => [
                'lvl'           => '0',
                'display'       => 'elective',
                'definition'    => 'Beneficial to the patient but not essential for survival.'
            ],
            'EM' => [
                'lvl'           => '0',
                'display'       => 'emergency',
                'definition'    => 'An unforeseen combination of circumstances or the resulting state that calls for immediate action.'
            ],
            'P' => [
                'lvl'           => '0',
                'display'       => 'preop',
                'definition'    => 'Used to indicate that a service is to be performed prior to a scheduled surgery. When ordering a service and using the pre-op priority, a check is done to see the amount of time that must be allowed for performance of the service. When the order is placed, a message can be generated indicating the time needed for the service so that it is not ordered in conflict with a scheduled operation.'
            ],
            'PRN' => [
                'lvl'           => '0',
                'display'       => 'as needed',
                'definition'    => 'An "as needed" order should be accompanied by a description of what constitutes a need. This description is represented by anobservation service predicate as a precondition.'
            ],
            'R' => [
                'lvl'           => '0',
                'display'       => 'routine',
                'definition'    => 'Routine service, do at usual work hours.'
            ],
            'RR' => [
                'lvl'           => '0',
                'display'       => 'rush reporting',
                'definition'    => 'A report should be prepared and sent as quickly as possible.'
            ],
            'S' => [
                'lvl'           => '0',
                'display'       => 'stat',
                'definition'    => 'With highest priority (e.g., emergency).'
            ],
            'T' => [
                'lvl'           => '0',
                'display'       => 'timing critical',
                'definition'    => 'It is critical to come as close as possible to the requested time (e.g., for a through antimicrobial level).'
            ],
            'UD' => [
                'lvl'           => '0',
                'display'       => 'use as directed',
                'definition'    => 'Drug is to be used as directed by the prescriber.'
            ],
            'UR' => [
                'lvl'           => '0',
                'display'       => 'urgent',
                'definition'    => 'Calls for prompt action.'
            ]
        ];
    }
}
