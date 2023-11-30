<?php

namespace Debva\Nix\Extension\SatuSehat;

trait Practitioner
{
    public function practitionerByNIK($nik)
    {
        $response = http()->get(
            "{$this->baseURL}/Practitioner?identifier=https://fhir.kemkes.go.id/id/nik|{$nik}",
            ["Authorization: Bearer {$this->token}"]
        );

        return $this->response($response);
    }

    public function practitionerByNameGenderBirthdate($name, $gender, $birthdate)
    {
        $response = http()->get(
            "{$this->baseURL}/Practitioner?name={$name}&gender={$gender}&birthdate={$birthdate}",
            ["Authorization: Bearer {$this->token}"]
        );

        return $this->response($response);
    }

    public function practitionerByID($id)
    {
        $response = http()->get(
            "{$this->baseURL}/Practitioner/{$id}",
            ["Authorization: Bearer {$this->token}"]
        );

        return $this->response($response);
    }
}
