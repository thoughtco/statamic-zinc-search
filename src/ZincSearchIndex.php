<?php

namespace Thoughtco\ZincSearch;

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

        parent::__construct($name, $config, $locale);
    }

    public function search($searchString)
    {
        return $this->queryBuilder->where(function ($query) use ($searchString) {
            foreach (($this->config['searchable_fields'] ?: ['_id']) as $index => $field) {
                $query->{$index == 0 ? 'where' : 'orWhere'}($field, 'like', $searchString.'%');
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
        throw new \Exception('ZincSearch: '.$error);
    }
}
