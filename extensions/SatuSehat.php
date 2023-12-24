<?php

namespace Debva\Nix\Extension;

use Debva\Nix\Extension\SatuSehat\Auth;
use Debva\Nix\Extension\SatuSehat\Base;
use Debva\Nix\Extension\SatuSehat\Location;
use Debva\Nix\Extension\SatuSehat\Organization;
use Debva\Nix\Extension\SatuSehat\Patient;
use Debva\Nix\Extension\SatuSehat\Practitioner;

class SatuSehat extends Base
{
    use Auth, Practitioner, Organization, Location, Patient;

    protected $organizationID;

    protected $clientKey;

    protected $secretKey;

    protected $token;

    protected $env;

    protected $verbose;

    protected $authURL;

    protected $baseURL;

    protected $consentURL;

    protected $headers = [];

    protected $module;

    protected $mapping;

    protected $data = [];

    public function __construct($organizationID, $clientKey, $secretKey, $env = 'development', $verbose = true)
    {
        $this->method = isMethod();

        $this->organizationID = $organizationID;

        $this->clientKey = $clientKey;

        $this->secretKey = $secretKey;

        $this->env = $env;

        $this->verbose = $verbose;

        switch (strtolower($env)) {
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

            default:
                throw new \Exception('Environment not supported!');
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
        $this->headers = [
            "Authorization: Bearer {$this->token}",
            ($this->method !== 'PATCH') ? 'Content-Type: application/json' : 'Content-Type: application/json-patch+json'
        ];
        return $this;
    }

    public function get($key, $search = false, $column = false)
    {
        $data = $this->data;

        foreach (explode('.', $key) as $key) {
            $data = isset($data[$key]) ? $data[$key] : null;
        }

        if (is_array($data) && $search && is_array($search) && $column) {
            foreach ($search as $key => $value) {
                $index = array_search($value, array_column($data, $key));
                $data = $index !== false ? $data[$index] : [];
            }

            return isset($data[$column]) ? $data[$column] : null;
        }

        return $data;
    }

    public function getToken()
    {
        return $this->token;
    }

    protected function response($data)
    {
        $data = is_string($data) ? json_decode($data, true) : $data;

        if (is_null($data)) {
            $code = 500;
            $error = 'Unable to connect to Satu Sehat server';
        }

        if (isset($data['issue']) && count($data['issue']) > 0) {
            foreach ($data['issue'] as $issue) {
                if (isset($issue['diagnostics'])) {
                    $code = 400;
                    $error = $issue['diagnostics'];
                } else if (isset($issue['details']['text'])) {
                    $code = 400;
                    $error = $issue['details']['text'];
                } else {
                    $code = 404;
                    $error = 'Unknown issue!';
                }
            }
        }

        if (isset($data['fault']['faultstring'])) {
            $code = 500;
            $error = $data['fault']['faultstring'];
        }

        if (isset($data['Error'])) {
            $code = 500;
            $error = $data['Error'];
        }

        if (isset($data['success']) && $data['success'] === false && isset($data['message'])) {
            $code = 403;
            $error = $data['message'];
        }

        if (isset($error, $code)) {
            if ($this->verbose) {
                throw new \Exception($error, $code);
            } else {
                $data = [];
            }
        }

        return $this->data = empty($data) ? [] : ($this->mapping ? $this->mapping->response($data) : $data);
    }
}
