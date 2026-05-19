<?php

namespace DeepSeek\Wan;

class Model
{
    public function __construct(private readonly Config $config)
    {
    }

    public function list(): array
    {
        $http = new HttpClient($this->config);

        return $http->request('GET', '/models');
    }

    public function balance(): array
    {
        $http = new HttpClient($this->config);

        return $http->request('GET', '/user/balance');
    }
}
