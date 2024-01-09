<?php

namespace Debva\Nix\Extension\SatuSehat\ValueSet;

class ParticipantType
{
    public function __invoke()
    {
        return [
            'ADM' => [
                'system'        => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
                'display'       => 'admitter',
                'definition'    => 'The practitioner who is responsible for admitting a patient to a patient encounter.'
            ],
            'ATND' => [
                'system'        => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
                'display'       => 'attender',
                'definition'    => 'The practitioner that has responsibility for overseeing a patient\'s care during a patient encounter.'
            ],
            'CALLBCK' => [
                'system'        => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
                'display'       => 'callback contact',
                'definition'    => 'A person or organization who should be contacted for follow-up questions about the act in place of the author.'
            ],
            'CON' => [
                'system'        => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
                'display'       => 'consultant',
                'definition'    => 'An advisor participating in the service by performing evaluations and making recommendations.'
            ],
            'DIS' => [
                'system'        => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
                'display'       => 'discharger',
                'definition'    => 'The practitioner who is responsible for the discharge of a patient from a patient encounter.'
            ],
            'ESC' => [
                'system'        => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
                'display'       => 'escort',
                'definition'    => 'Only with Transportation services. A person who escorts the patient.'
            ],
            'REF' => [
                'system'        => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
                'display'       => 'referrer',
                'definition'    => 'A person having referred the subject of the service to the performer (referring physician). Typically, a referring physician will receive a report.'
            ],
            'SPRF' => [
                'system'        => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
                'display'       => 'secondary performer',
                'definition'    => 'A person assisting in an act through his substantial presence and involvement This includes: assistants, technicians, associates, or whatever the job titles may be.'
            ],
            'PPRF' => [
                'system'        => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
                'display'       => 'primary performer',
                'definition'    => 'The principal or primary performer of the act.'
            ],
            'PART' => [
                'system'        => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
                'display'       => 'Participation',
                'definition'    => 'Indicates that the target of the participation is involved in some manner in the act, but does not qualify how.'
            ],
            'translator' => [
                'system'        => 'http://terminology.hl7.org/CodeSystem/participant-type',
                'display'       => 'Translator',
                'definition'    => 'A translator who is facilitating communication with the patient during the encounter.'
            ],
            'emergency' => [
                'system'        => 'http://terminology.hl7.org/CodeSystem/participant-type',
                'display'       => 'Emergency',
                'definition'    => 'A person to be contacted in case of an emergency during the encounter.'
            ]
        ];
    }
}
