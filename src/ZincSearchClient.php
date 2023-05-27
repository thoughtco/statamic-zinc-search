<?php

namespace Thoughtco\ZincSearch;

use Illuminate\Support\Facades\Http;

class ZincSearchClient
{
    private $client;

    public function __construct(array $credentials)
    {
        $this->client = Http::withBasicAuth($credentials['username'] ?? '', $credentials['password'] ?? '')
            ->baseUrl($credentials['url']);
    }

    public function index($name, $config, $locale)
    {
        return new ZincSearchIndex($this->client, $name, $config, $locale);
    }
}
