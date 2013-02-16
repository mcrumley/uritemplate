uritemplate
===========

RFC 6570 URI Template processor

Examples
--------

    <?php
    use UriTemplate;
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

Static Class
------------
There is also a static class that does processes templates without creating an object.

    <?php
    use UriTemplate;
    echo UriTemplate::expand('http://github.com{/project}', array(
        'project' => array('mcrumley', 'uritemplate')
    ));
    // prints http://github.com/mcrumley/uritemplate

The static class has additional methods for getting information about the template

    echo implode(', ', (UriTemplate::getVariables('http://{.url}{/project}')));
    // prints url, project

    $templateErrors = UriTemplate::getErrors('http://{.url:error}{=project}'));
    echo implode(', ', $templateErrors);
    // prints Malformed varspec: ".url:error", Malformed varspec: "=project"
