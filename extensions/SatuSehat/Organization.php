<?php

namespace Debva\Nix\Extension\SatuSehat;

use Debva\Nix\Extension\SatuSehat\Mapping\Organization\Create;
use Debva\Nix\Extension\SatuSehat\Mapping\Organization\Search;
use Debva\Nix\Extension\SatuSehat\Mapping\Organization\Update;

trait Organization
{
    public function organization()
    {
        $this->module = __FUNCTION__;
        return $this;
    }

    protected function organizationCreate($data)
    {
        $this->mapping = new Create;

        $response = http()->post(
            "{$this->baseURL}/Organization",
            $this->headers,
            $this->mapping->body(array_merge($data, ['IHS' => $this->organizationID]))
        );

        return $this->response($response);
    }

    protected function organizationSearch($search)
    {
        $this->mapping = new Search;

        $search = is_array($search) ? '?' . http_build_query($search) : $search;

        $response = http()->get("{$this->baseURL}/Organization/{$search}", $this->headers);

        return $this->response($response);
    }

    protected function organizationUpdate($id, $data)
    {
        $this->mapping = new Update($this->method);

        $response = http()->{$this->method}(
            "{$this->baseURL}/Organization/{$id}",
            $this->headers,
            $this->mapping->body(array_merge($data, [
                'id'    => $id,
                'IHS'   => $this->organizationID
            ]))
        );

        return $this->response($response);
    }
}
