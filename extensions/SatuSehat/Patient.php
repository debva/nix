<?php

namespace Debva\Nix\Extension\SatuSehat;

use Debva\Nix\Extension\SatuSehat\Mapping\Patient\Create;
use Debva\Nix\Extension\SatuSehat\Mapping\Patient\Search;

trait Patient
{
    public function patient()
    {
        $this->module = __FUNCTION__;
        return $this;
    }

    public function patientCreate($data)
    {
        $this->mapping = new Create;

        $response = http()->post(
            "{$this->baseURL}/Patient",
            $this->headers,
            $this->mapping->body($data)
        );

        return $this->response($response);
    }

    public function patientSearch($search)
    {
        $this->mapping = new Search;

        $search = (is_array($search) && isset($search['identifier']) && isset($search['identifierValue']))
            ? ['identifier' => "https://fhir.kemkes.go.id/id/{$search['identifier']}|{$search['identifierValue']}"] : $search;
        $search = is_array($search) ? '?' . http_build_query($search) : $search;

        $response = http()->get("{$this->baseURL}/Patient/{$search}", $this->headers);

        return $this->response($response);
    }
}
