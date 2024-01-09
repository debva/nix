<?php

namespace Debva\Nix\Extension\SatuSehat\ValueSet;

class EventStatus
{
    public function __invoke()
    {
        return  [
            'preparation' => [
                'display'          => 'Preparation',
                'definition'       => 'The core event has not started yet, but some staging activities have begun (e.g. surgical suite preparation). Preparation stages may be tracked for billing purposes.',
                'canonical_status' => '~planned',
            ],
            'in-progress' => [
                'display'          => 'In Progress',
                'definition'       => 'The event is currently occurring.',
                'canonical_status' => '~active',
            ],
            'not-done' => [
                'display'          => 'Not Done',
                'definition'       => 'The event was terminated prior to any activity beyond preparation. I.e. The \'main\' activity has not yet begun. The boundary between preparatory and the \'main\' activity is context-specific.',
                'canonical_status' => '~abandoned',
            ],
            'on-hold' => [
                'display'          => 'On Hold',
                'definition'       => 'The event has been temporarily stopped but is expected to resume in the future.',
                'canonical_status' => '~suspended',
            ],
            'stopped' => [
                'display'          => 'Stopped',
                'definition'       => 'The event was terminated prior to the full completion of the intended activity but after at least some of the \'main\' activity (beyond preparation) has occurred.',
                'canonical_status' => '~failed',
            ],
            'completed' => [
                'display'          => 'Completed',
                'definition'       => 'The event has now concluded.',
                'canonical_status' => '~complete',
            ],
            'entered-in-error' => [
                'display'          => 'Entered in Error',
                'definition'       => 'This electronic record should never have existed, though it is possible that real-world decisions were based on it. (If real-world activity has occurred, the status should be "stopped" rather than "entered-in-error".).',
                'canonical_status' => '~error',
            ],
            'unknown' => [
                'display'          => 'Unknown',
                'definition'       => 'The authoring/source system does not know which of the status values currently applies for this event. Note: This concept is not to be used for "other" - one of the listed statuses is presumed to apply, but the authoring/source system does not know which.',
                'canonical_status' => '~unknown',
            ],
        ];
    }
}
