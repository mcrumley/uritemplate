UriTemplate
===========

PHP [RFC 6570](http://tools.ietf.org/html/rfc6570) URI Template processor

Installation
------------
If you use Composer, just add this to your composer.json
```javascript
"uri-template/uri-template": "*"
```

Or copy the files into your project's lib folder.

Reqirements
-----------
UriTemplate requires PHP 5.3 or greater

Examples
--------
```php
<?php
use UriTemplate\Processor;
$url = new Processor('http://github.com{/project}', array(
    'project' => array('mcrumley', 'uritemplate')
));
echo $url->process();
// prints http://github.com/mcrumley/uritemplate

// or
$url = new Processor();
$url->setTemplate('http://github.com{/project}');
$url->setData(array('project' => array('mcrumley', 'uritemplate')));
echo $url->process();
// prints http://github.com/mcrumley/uritemplate
```

Static Class
------------
There is also a static class that does processes templates without creating an object.
```php
<?php
use UriTemplate\UriTemplate;
echo UriTemplate::expand('http://github.com{/project}', array(
    'project' => array('mcrumley', 'uritemplate')
));
// prints http://github.com/mcrumley/uritemplate
```

The static class has additional methods for getting information about the template

```php
echo implode(', ', (UriTemplate::getVariables('http://{.url}{/project}')));
// prints url, project

$templateErrors = UriTemplate::getErrors('http://{.url:error}{=project}'));
echo implode(', ', $templateErrors);
// prints Malformed varspec: ".url:error", Malformed varspec: "=project"
```

Tests
-----
Each version is tested against all samples available at https://github.com/uri-templates/uritemplate-test.

License
-------
© Michael Crumley

MIT licensed. For the full copyright and license information, please view the LICENSE
file distributed with the source code.
