<?php

namespace Debva\Nix\Extension\SatuSehat\ValueSet;

class LocationType
{
    public function __invoke()
    {
        return [
            'si' => [
                'display'       => 'Site',
                'definition'    => 'A collection of buildings or other locations such as a site or a campus.'
            ],
            'bu' => [
                'display'       => 'Building',
                'definition'    => 'Any Building or structure. This may contain rooms, corridors, wings, etc. It might not have walls, or a roof, but is considered a defined/allocated space.'
            ],
            'wi' => [
                'display'       => 'Wing',
                'definition'    => 'A Wing within a Building, this often contains levels, rooms and corridors.'
            ],
            'wa' => [
                'display'       => 'Ward',
                'definition'    => 'A Ward is a section of a medical facility that may contain rooms and other types of location.'
            ],
            'lvl' => [
                'display'       => 'Level',
                'definition'    => 'A Level in a multi-level Building/Structure.'
            ],
            'co' => [
                'display'       => 'Corridor',
                'definition'    => 'Any corridor within a Building, that may connect rooms.'
            ],
            'ro' => [
                'display'       => 'Room',
                'definition'    => 'A space that is allocated as a room, it may have walls/roof etc., but does not require these.'
            ],
            'bd' => [
                'display'       => 'Bed',
                'definition'    => 'A space that is allocated for sleeping/laying on. This is not the physical bed/trolley that may be moved about, but the space it may occupy.'
            ],
            've' => [
                'display'       => 'Vehicle',
                'definition'    => 'A means of transportation.'
            ],
            'ho' => [
                'display'       => 'House',
                'definition'    => 'A residential dwelling. Usually used to reference a location that a person/patient may reside.'
            ],
            'ca' => [
                'display'       => 'Cabinet',
                'definition'    => 'A container that can store goods, equipment, medications or other items.'
            ],
            'rd' => [
                'display'       => 'Road',
                'definition'    => 'A defined path to travel between 2 points that has a known name.'
            ],
            'area' => [
                'display'       => 'Area',
                'definition'    => 'A defined physical boundary of something, such as a flood risk zone, region, postcode.'
            ],
            'jdn' => [
                'display'       => 'Jurisdiction',
                'definition'    => 'A wide scope that covers a conceptual domain, such as a Nation (Country wide community or Federal Government - e.g. Ministry of Health), Province or State (community or Government), Business (throughout the enterprise), Nation with a business scope of an agency (e.g. CDC, FDA etc.) or a Business segment (UK Pharmacy), not just a physical boundary.'
            ],
        ];
    }
}
