<?php

namespace Thoughtco\ZincSearch;

use Statamic\Facades\Search;
use Statamic\Providers\AddonServiceProvider;
use Thoughtco\ZincSearch\ZincSearchClient;

class ServiceProvider extends AddonServiceProvider
{
    public function bootAddon()
    {
        //$this->bootAddonConfig();
        $this->bootSearchClient();
    }

//     protected function bootAddonConfig()
//     {
//         $this->mergeConfigFrom(__DIR__.'/../config/zincsearch.php', 'statamic.zincsearch');
//
//         if ($this->app->runningInConsole()) {
//             $this->publishes([
//                 __DIR__.'/../config/zincsearch.php' => config_path('statamic/zincsearch.php'),
//             ], 'statamic-zincsearch');
//         }
//
//         return $this;
//     }

    protected function bootSearchClient()
    {
        Search::extend('zincsearch', function ($app, array $config, $name, $locale) {
            $client = new ZincSearchClient($config['credentials']);
            return $client->index($name, $config, $locale);
        });
    }
}
