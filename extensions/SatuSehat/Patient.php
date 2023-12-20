<?php

namespace Debva\Nix\Extension\SatuSehat;

use Debva\Nix\Extension\SatuSehat\Mapping\Patient\Search;

trait Patient
{
    public function patient()
    {
        $this->module = __FUNCTION__;
        return $this;
    }

    public function patientSearch($search)
    {
        $this->mapping = new Search;

        $search = (is_array($search) && isset($search['identifier'])) ? ['identifier' => "https://fhir.kemkes.go.id/id/nik|{$search['identifier']}"] : $search;
        $search = is_array($search) ? '?' . http_build_query($search) : $search;

        $response = http()->get("{$this->baseURL}/Patient/{$search}", $this->headers);

        return $this->response($response);
    }
}
