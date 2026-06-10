# jsonredirects
Json file based redirects with exact match and wildcard * matching for changing higher up paths with sub categories so the sub categories get redirected as well.

# Use

```php
True\Redirects::redirect(['request'=>$_SERVER['REQUEST_URI'], 'lookup'=>BP.'/redirects.json', 'type'=>'301']);
```

# JSON File 

```json
{
	"path1/path2/":"path3/",
	"path4/path5/*":"path4/path6/*",
	"path7/path9/*":"path7/path10/",
}
```

*First line:*
Simple redirect. Key: request uri and value: redirected uri

*Second line:*
Matches "path4/path5/path8/" redirects to "path4/path6/path8/" The path that matches the * on the request is moved over to the end of the redirect uri in place of the *

*Third line:*
Matches "path7/path9/path8/" redirects to "path7/path10/" The path that matches the * on the request is NOT moved over to the end of the redirect uri because there is no * on the redirect

# Editing JSON file with PHP

## Add redirect
```php
$Redirects = new True\Redirects(BP.'/config/redirects.json');
$Redirects->add($App->request->post->fromUrl, $App->request->post->toUrl);
```

## Remove redirect
```php
$Redirects = new True\Redirects(BP.'/config/redirects.json');
$Redirects->remove($App->request->delete->fromUrl);
```

## Get redirects list
```php
$Redirects = new True\Redirects(BP.'/config/redirects.json');
$list = $Redirects->getRedirectsList();

// array ['from-url'=>'to-url', 'from-url'=>'to-url']
```

## Clean URL
```php
$Redirects = new True\Redirects(BP.'/config/redirects.json');
$cleanedURL = $Redirects->cleanUrl($url);
```

# Notes

This code works well in a routes script at the top before other custom routes are checked.

It is only designed to match the end of the url and not a middle part like path/*/path 
If that would be helpful, let me know and I will see what I can do.

Thanks!

I hope this is helpful.