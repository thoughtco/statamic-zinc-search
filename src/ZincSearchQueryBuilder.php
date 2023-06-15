<?php

namespace Thoughtco\ZincSearch;

use Statamic\Contracts\Search\Result;
use Statamic\Data\DataCollection;
use Statamic\Search\Index;
use Statamic\Search\PlainResult;
use Statamic\Search\Searchables\Providers;
use Statamic\Support\Str;

class ZincSearchQueryBuilder extends LaravelElasticsearchQueryBuilder
{
    protected $index;
    protected $withData = true;

    public function setIndex(Index $index)
    {
        $this->index = $index;

        return $this;
    }

    public function withData(bool $with)
    {
        $this->withData = $with;

        return $this;
    }

    public function withoutData()
    {
        $this->withData = false;

        return $this;
    }

    public function getBaseItems($results)
    {
        if (! $this->withData) {
            return $this->collect($results)
                ->map(function ($result) {
                    $result = $result['_source'];
                    $result['reference'] = $result['_id'];
                    return new PlainResult($result);
                })
                ->each(fn (Result $result, $i) => $result->setIndex($this->index)->setScore($results[$i]['_score'] ?? null));
        }

        return $this->collect($results)->groupBy(function ($result) {
            return Str::before($result['_id'], '::');
        })->flatMap(function ($results, $prefix) {
            $results = $results->map(function ($result, $idx) {
                $result['_count'] = $idx;
                return $result;
            })->keyBy('_id');

            $ids = $results->map(fn ($result) => Str::after($result['_id'], $prefix.'::'))->values()->all();

            return app(Providers::class)
                ->getByPrefix($prefix)
                ->find($ids)
                ->map->toSearchResult()
                ->each(function (Result $result) use ($results) {
                    return $result
                        ->setIndex($this->index)
                        ->setRawResult($raw = $results[$result->getReference()])
                        ->setScore($raw['_count'] ?? 9999);
                });
        })
        ->sortBy->getScore()
        ->values();
    }

    protected function collect($items = [])
    {
        return new DataCollection($items);
    }

    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $result = parent::paginate($perPage);
        $result->setCollection($this->getBaseItems($result->getCollection()));
        return $result;
    }
}
