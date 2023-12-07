<?php

namespace Debva\Nix\Extension\SatuSehat\DataType;

use Debva\Nix\Extension\SatuSehat\Base;

class Reference extends Base
{
    public function __invoke($data, $reference)
    {
        return array_filter([
            'reference' => isset($data['reference']) ? "{$reference}/{$data['reference']}" : null,
            'display' => isset($data['display']) ? $data['display'] : null
        ]);
    }
}
