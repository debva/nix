<?php

namespace Debva\Nix\Extension\SatuSehat\ValueSet;

class NameUse
{
    public function __invoke()
    {
        return [
            'usual' => [
                'lvl'        => 0,
                'display'    => 'Usual',
                'definition' => 'Known as/conventional/the one you normally use.',
            ],
            'official' => [
                'lvl'        => 0,
                'display'    => 'Official',
                'definition' => 'The formal name as registered in an official (government) registry, but which name might not be commonly used. May be called "legal name".',
            ],
            'temp' => [
                'lvl'        => 0,
                'display'    => 'Temp',
                'definition' => 'A temporary name. Name.period can provide more detailed information. This may also be used for temporary names assigned at birth or in emergency situations.',
            ],
            'nickname' => [
                'lvl'        => 0,
                'display'    => 'Nickname',
                'definition' => 'A name that is used to address the person in an informal manner, but is not part of their formal or usual name.',
            ],
            'anonymous' => [
                'lvl'        => 0,
                'display'    => 'Anonymous',
                'definition' => 'Anonymous assigned name, alias, or pseudonym (used to protect a person\'s identity for privacy reasons).',
            ],
            'old' => [
                'lvl'        => 0,
                'display'    => 'Old',
                'definition' => 'This name is no longer in use (or was never correct, but retained for records).',
            ],
            'maiden' => [
                'lvl'        => 1,
                'display'    => 'Name changed for Marriage',
                'definition' => 'A name used prior to changing name because of marriage. This name use is for use by applications that collect and store names that were used prior to a marriage. Marriage naming customs vary greatly around the world, and are constantly changing. This term is not gender specific. The use of this term does not imply any particular history for a person\'s name.',
            ],
        ];
    }
}
