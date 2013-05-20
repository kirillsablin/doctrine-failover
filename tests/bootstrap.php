<?php
chdir(dirname(__DIR__));
require 'vendor/autoload.php';
require 'vendor/phpunit/phpunit/PHPUnit/Framework/Assert/Functions.php';

$classLoader = new \Doctrine\Common\ClassLoader('Doctrine\Tests\DBAL\Functional\Failover', __DIR__.'/');
$classLoader->register();

$classLoader = new \Doctrine\Common\ClassLoader('Doctrine\Tests', dirname(__DIR__).'/vendor/doctrine/dbal/tests/');
$classLoader->register();
