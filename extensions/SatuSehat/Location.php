<?php

namespace Debva\Nix\Extension\SatuSehat;

use Debva\Nix\Extension\SatuSehat\Mapping\Location\Create;
use Debva\Nix\Extension\SatuSehat\Mapping\Location\Search;
use Debva\Nix\Extension\SatuSehat\Mapping\Location\Update;

trait Location
{
    public function location()
    {
        $this->module = __FUNCTION__;
        return $this;
    }

    protected function locationCreate($data)
    {
        $this->mapping = new Create;

        $response = http()->post(
            "{$this->baseURL}/Location",
            $this->headers,
            $this->mapping->body(array_merge($data, ['IHS' => $this->organizationID]))
        );

        return $this->response($response);
    }

    protected function locationSearch($search)
    {
        $this->mapping = new Search;

        $search = (is_array($search) && isset($search['identifier'])) ? ['identifier' => "http://sys-ids.kemkes.go.id/location/{$search['identifier']}"] : $search;
        $search = is_array($search) ? '?' . http_build_query($search) : $search;

        $response = http()->get("{$this->baseURL}/Location/{$search}", $this->headers);

        return $this->response($response);
    }

    protected function locationUpdate($id, $data)
    {
        $this->mapping = new Update($this->method);

        $response = http()->{$this->method}(
            "{$this->baseURL}/Location/{$id}",
            $this->headers,
            $this->mapping->body(array_merge($data, [
                'id'    => $id,
                'IHS'   => $this->organizationID
            ]))
        );

        return $this->response($response);
    }
}
