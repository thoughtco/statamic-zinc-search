# ZincSearch

> ZincSearch is a Statamic addon to speed up your listing views in the control by using the power of [ZincSearch](https://zincsearch-docs.zinc.dev).

## Features

This addon makes your control panel listings go super-dooper-fast.

## How to Install

Ensure you have a working [ZincSearch installation](https://zincsearch-docs.zinc.dev/installation/).

Run the following command from your project root:

``` bash
composer require thoughtco/zinc-search
```

Add the `zincsearch` driver to the drivers array in `config/statamic/search.php`:

```php
        'zincsearch' => [
             'credentials' => [
                'url' => env('ZINCSEARCH_URL', ''),
                'username' => env('ZINCSEARCH_USERNAME', ''),
                'password' => env('ZINCSEARCH_PASSWORD', ''),
            ],           
        ],
```

Add the variables to your `.env` file for `ZINCSEARCH_URL`, `ZINCSEARCH_USERNAME` and `ZINCSEARCH_PASSWORD`.

## How to Use

Set up a search index for the collection e.g.

```php
'pages' => [
    'driver' => 'zincsearch',
    'searchables' => ['collection:pages'],
    'fields' => ['id', 'title', 'url', 'content', 'status'],
    'searchable_fields' => ['title, 'slug'],
    'settings' => [],
],
```

Then apply this to your collection YAML definition with a key of search_index e.g.

```yaml
title: Pages
revisions: false
title_format: null
sort_by: title
sort_dir: asc
preview_targets:
  -
    label: Entry
    url: '{permalink}'
structure:
  root: true
search_index: pages
```

## Antlers / Front end

To make use of this use the speed of this search in your front end, use the `search_index` tag instead of `collection`.

e.g.

```antlers
{{ search:results index="pages" supplement_data="false" for="*" as="results" limit="10" offset="0" paginate="10" }}
    {{ if no_results }}
    No results
    {{ else }}
        {{ results }}
        <p>{{ id }} - {{ title }}</p>
        {{ /results }}
    {{ /if }}
{{ /search:results }}
```



# TO DO

PR to core to allow entry listing to ALWAYS use search index - maybe based on config value in search index being present?

PR to core to allow search index tag to accept query scopes

