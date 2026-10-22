# Redirects

JSON-file redirects with exact and wildcard matching. One class for the
framework and every module. The default file is `app/data/redirects.json`
(created as `{}` when missing).

## Serve redirects

In `app/routes.php`, before any other route:

```php
True\Redirects::redirect(['request' => $_SERVER['REQUEST_URI'], 'type' => '301']);
```

`lookup` (file path) is optional. Only the path is matched; the query string
is passed along unless the target has its own. Types: 301, 302, 303, 307, 308.
Returns false when nothing matches.

## JSON file

```json
{
	"old-page": "new-page",
	"old-services/*": "services/*",
	"old-blog/*": "blog",
	"partner": "https://example.com/"
}
```

Paths are stored without leading or trailing slashes (older files with a
trailing slash still match). A trailing `*` on From matches anything below
it; a `*` on To receives what it matched. Exact matches win over wildcards,
and the longest wildcard prefix wins.

## Editing in PHP

```php
$R = new True\Redirects();                 // or new True\Redirects($file)
$R->add('/old', '/new');                    // add or replace
$R->remove('/old');
$R->pageMoved('/blog/old', '/blog/new');    // slug change: no loops or chains
$R->pageMoved('/about', '/company', true);  // also about/* -> company/*
$R->getRedirectsList();                     // ['from' => 'to', ...]
$R->match('/old');                          // '/new' or null
$R->isRedirected('/old');                   // bool
```
