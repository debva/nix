<?php

namespace Debva\Nix\Extension\SatuSehat\ValueSet;

class OrganizationType
{
    public function __invoke()
    {
        return [
            'prov' => [
                'display'       => 'Healthcare Provider',
                'definition'    => 'An organization that provides healthcare services.'
            ],
            'dept' => [
                'display'       => 'Hospital Department',
                'definition'    => 'A department or ward within a hospital (Generally is not applicable to top level organizations).'
            ],
            'team' => [
                'display'       => 'Organizational Team',
                'definition'    => 'An organizational team is usually a grouping of practitioners that perform a specific function within an organization (which could be a top level organization, or a department).'
            ],
            'govt' => [
                'display'       => 'Government',
                'definition'    => 'A political body, often used when including organization records for government bodies such as a Federal Government, State or Local Government.'
            ],
            'ins' => [
                'display'       => 'Insurance Company',
                'definition'    => 'A company that provides insurance to its subscribers that may include healthcare related policies.'
            ],
            'pay' => [
                'display'       => 'Payer',
                'definition'    => 'A company, charity, or governmental organization, which processes claims and/or issues payments to providers on behalf of patients or groups of patients.'
            ],
            'edu' => [
                'display'       => 'Educational Institute',
                'definition'    => 'An educational institution that provides education or research facilities.'
            ],
            'reli' => [
                'display'       => 'Religious Institution',
                'definition'    => 'An organization that is identified as a part of a religious institution.'
            ],
            'crs' => [
                'display'       => 'Clinical Research Sponsor',
                'definition'    => 'An organization that is identified as a Pharmaceutical/Clinical Research Sponsor.'
            ],
            'cg' => [
                'display'       => 'Community Group',
                'definition'    => 'An un-incorporated community group.'
            ],
            'bus' => [
                'display'       => 'Non-Healthcare Business or Corporation',
                'definition'    => 'An organization that is a registered business or corporation but not identified by other types.'
            ],
            'other' => [
                'display'       => 'Other',
                'definition'    => 'Other type of organization not already specified.'
            ]
        ];
    }
}
