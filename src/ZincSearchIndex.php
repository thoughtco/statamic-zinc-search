<?php

namespace Thoughtco\ZincSearch;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Statamic\Search\Documents;
use Statamic\Search\Index;
use Thoughtco\ZincSearch\LaravelElasticsearchQueryBuilder;

class ZincSearchIndex extends Index
{
    private $client;
    private $queryBuilder;
    private $results;

    public function __construct($client, $name, $config, $locale)
    {
        $this->client = $client;

        $this->queryBuilder = (new ZincSearchQueryBuilder($config['credentials']))
            ->setOptions([
                'index_name' => $name,
                'type_name' => false,
            ])
            ->setIndex($this);

        $mappings = $this->client->get('api/'.$name.'/_mapping')->json() ?? [];

        $this->queryBuilder->setMappingProperties(Arr::get($mappings, $name.'.mappings.properties', []));

        parent::__construct($name, $config, $locale);
    }

    public function query()
    {
        return $this->search('');
    }

    public function search($searchString)
    {
        return $this->queryBuilder->where(function ($query) use ($searchString) {
            foreach (($this->config['searchable_fields'] ?: ['_id']) as $index => $field) {
                $query->{$index == 0 ? 'where' : 'orWhere'}($field, 'like', '%'.$searchString.'%');
            }
        });
    }

    public function delete($document)
    {
        $this->client->delete('api/index/'.$this->name.'/_doc/'.$document->getSearchReference());
    }

    public function exists()
    {
        $response = $this->client->head('api/index/'.$this->name);
        return $response->status() == 200;
    }

    protected function insertDocuments(Documents $documents)
    {
        $this->updateMappings($documents->first());

        $documents = $documents->map(function ($item, $id) {
            $item['_id'] = $id;

            return json_encode($item);
        })
            ->join("\n");

        $result = $this->client->withBody($documents, 'application/json')
            ->post('api/'.$this->name.'/_multi');

        if ($error = ($searchResults['error'] ?? false)) {
            $this->throwException($error);
        }
    }

    protected function deleteIndex()
    {
        $this->client->delete('api/index/'.$this->name);
    }

    private function throwException($error)
    {
        throw new Exception('ZincSearch: '.$error);
    }

    private function updateMappings($document)
    {
        // do we need to update mappings each time?
        //https://zincsearch-docs.zinc.dev/api/index/update-mapping/#request
        $mappings = collect($document)->flatMap(function($value, $handle) {

            $type = 'text';
            $extra = [];
            if (is_bool($value)) {
                $type = 'bool';
            } else if (is_integer($value) || is_float($value)) {
                $type = 'numeric';
            } else if ($value) {
                try {
                    $value = Carbon::parse($value)->format('c');
                    $type = 'date';
                    $extra['format'] = '2006-01-02T15:04:05+07:00';
                } catch (\Throwable $e) { }
            }

            return [
                $handle => array_merge([
                    'type' => $type,
                    'index' => true,
                    'store' => true,
                    'sortable' => true,
                    'aggregatable' => false,
                    'highlightable' => true,
                ], $extra)
            ];
        })
            ->all();

        $mappings = array_merge($mappings, Arr::get($this->config, 'settings.mappings', []));

        $response = $this->client->withBody(json_encode(['properties' => $mappings]), 'application/json')
            ->put('api/'.$this->name.'/_mapping');
    }
}
