<?php

if (!is_file(dirname(__DIR__).'/vendor/autoload.php')) {
    exit('Run `composer install`'.PHP_EOL);
}

$loader = include dirname(__DIR__).'/vendor/autoload.php';
$loader->register('UriTemplate/Test', __DIR__);
