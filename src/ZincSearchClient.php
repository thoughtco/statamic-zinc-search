<?php

namespace Thoughtco\ZincSearch;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Statamic\Facades\Collection;

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

        $listableFields = [];
        if (count($config['searchables']) == 1) {
            $collectionHandle = Str::after($config['searchables'][0], ':');
            $collection = Collection::find($collectionHandle);
            if ($collection) {
                $listableFields = $collection->entryBlueprint()->columns()->rejectUnlisted()->map->field()->values();
            }
        }

        $config['fields'] = collect($config['fields'])
            ->merge($config['searchable_fields'])
            ->merge($listableFields)
            ->flatten()
            ->unique()
            ->filter()
            ->all();

        return new ZincSearchIndex($this->client, $name, $config, $locale);
    }
}
