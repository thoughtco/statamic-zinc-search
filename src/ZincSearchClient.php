<?php

namespace Thoughtco\ZincSearch;

use Illuminate\Support\Arr;
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
        if (! Arr::get($config, 'searchable_fields')) {
            $config['searchable_fields'] = false;
        }

        $config['fields'] = collect($config['fields'])
            ->merge($config['searchable_fields'])
            ->flatten()
            ->unique()
            ->filter()
            ->all();

        return new ZincSearchIndex($this->client, $name, $config, $locale);
    }
}
