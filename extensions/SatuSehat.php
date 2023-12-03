<?php

namespace Debva\Nix\Extension;

use Debva\Nix\Extension\SatuSehat\Auth;
use Debva\Nix\Extension\SatuSehat\Organization;

class SatuSehat
{
    use Auth, Organization;

    protected $organizationID;

    protected $clientKey;

    protected $secretKey;

    protected $token;

    protected $env;

    protected $authURL;

    protected $baseURL;

    protected $consentURL;

    protected $headers = [];

    protected $module;

    protected $mapping;

    public function __construct($organizationID, $clientKey, $secretKey, $env = 'development')
    {
        $this->organizationID = $organizationID;

        $this->clientKey = $clientKey;

        $this->secretKey = $secretKey;

        $this->env = $env;

        switch ($env) {
            case 'development':
                $this->authURL = 'https://api-satusehat-dev.dto.kemkes.go.id/oauth2/v1';
                $this->baseURL = 'https://api-satusehat-dev.dto.kemkes.go.id/fhir-r4/v1';
                $this->consentURL = 'https://api-satusehat-dev.dto.kemkes.go.id/consent/v1';
                break;

            case 'staging':
                $this->authURL = 'https://api-satusehat-stg.dto.kemkes.go.id/oauth2/v1';
                $this->baseURL = 'https://api-satusehat-stg.dto.kemkes.go.id/fhir-r4/v1';
                $this->consentURL = 'https://api-satusehat-stg.dto.kemkes.go.id/consent/v1';
                break;

            case 'production':
                $this->authURL = 'https://api-satusehat.kemkes.go.id/oauth2/v1';
                $this->baseURL = 'https://api-satusehat.kemkes.go.id/fhir-r4/v1';
                $this->consentURL = 'https://api-satusehat.dto.kemkes.go.id/consent/v1';
                break;
        }
    }

    public function __call($name, $arguments)
    {
        $method = "{$this->module}{$name}";
        if (method_exists($this, $method)) {
            return $this->$method(...$arguments);
        }

        throw new \Exception("Method {$name} does not exist!");
    }

    public function setToken($token)
    {
        if (!is_string($token)) {
            throw new \Exception('Token must be a string');
        }

        $this->token = $token;
        $this->headers = ["Authorization: Bearer {$this->token}", 'Content-Type: application/json'];
        return $this;
    }

    public function getToken()
    {
        return $this->token;
    }

    protected function response($data)
    {
        $data = is_string($data) ? json_decode($data, true) : $data;

        if (is_null($data)) {
            throw new \Exception('Unable to connect to Satu Sehat server');
        }

        if (isset($data['issue']) && count($data['issue']) > 0) {
            return [
                'errors' => array_map(function ($issue) {
                    if (isset($issue['diagnostics'])) {
                        throw new \Exception($issue['diagnostics'], 400);
                    }

                    if (isset($issue['details']['text'])) {
                        throw new \Exception($issue['details']['text'], 400);
                    }

                    throw new \Exception('Unknown issue!', 404);
                }, $data['issue'])
            ];
        }

        if (isset($data['fault']['faultstring'])) {
            throw new \Exception($data['fault']['faultstring'], 400);
        }

        if (isset($data['Error'])) {
            throw new \Exception($data['Error'], 400);
        }

        return $this->mapping ? $this->mapping->response($data) : $data;
    }
}
