<?php

$root = dirname(dirname(dirname(dirname(__FILE__))));
require_once "$root/engine/tests/phpunit/bootstrap.php";
require_once dirname(__DIR__) . "/autoloader.php";

_elgg_services()->views->setViewDir('foo/bar', __DIR__ . '/phpunit/test_files/views/', 'default');
_elgg_services()->views->setViewDir('foo/baz', __DIR__ . '/phpunit/test_files/views/', 'default');