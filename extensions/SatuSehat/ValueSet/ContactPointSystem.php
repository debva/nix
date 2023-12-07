<?php

namespace Debva\Nix\Extension\SatuSehat\ValueSet;

class ContactPointSystem
{
    public function __invoke()
    {
        return [
            'phone' => [
                'display'       => 'Phone',
                'definition'    => 'The value is a telephone number used for voice calls. Use of full international numbers starting with + is recommended to enable automatic dialing support but not required.'
            ],
            'fax' => [
                'display'       => 'Fax',
                'definition'    => 'The value is a fax machine. Use of full international numbers starting with + is recommended to enable automatic dialing support but not required.'
            ],
            'email' => [
                'display'       => 'Email',
                'definition'    => 'The value is an email address.'
            ],
            'pager' => [
                'display'       => 'Pager',
                'definition'    => 'The value is a pager number. These may be local pager numbers that are only usable on a particular pager system.'
            ],
            'url' => [
                'display'       => 'URL',
                'definition'    => 'A contact that is not a phone, fax, pager, or email address and is expressed as a URL. This is intended for various institutional or personal contacts including web sites, blogs, Skype, Twitter, Facebook, etc. Do not use for email addresses.'
            ],
            'sms' => [
                'display'       => 'SMS',
                'definition'    => 'A contact that can be used for sending an SMS message (e.g. mobile phones, some landlines).'
            ],
            'other' => [
                'display'       => 'Other',
                'definition'    => 'A contact that is not a phone, fax, pager, or email address and is not expressible as a URL. E.g. Internal mail address. This SHOULD NOT be used for contacts that are expressible as a URL (e.g. Skype, Twitter, Facebook, etc.). Extensions may be used to distinguish "other" contact types.'
            ]
        ];
    }
}
