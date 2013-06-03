doctrine-failover
=================

Doctrine mysql master-master failover with automatic failback


Installation
------------

At this moment only table based heartbeat strategy implemented (checking if replication is online)

1) Create heartbeat table at both masters
    Example SQL:


        CREATE TABLE `heartbeat` (
          `value` varchar(255) DEFAULT NULL,
          KEY `value` (`value`)
        )


2) If you use composer add to your composer.json

        "require": {
         ....
            "kirillsablin/doctrine-failover": "dev-master"
         ....
         }

2a) If you don't use composer copy add contents of src folder to you project and setup autoload if necessary

3) Add necessary configuration to to your Doctrine's config


Doctrine configuration example
------------------------------

```php

$config = array(
                'driver' => 'pdo_mysql',
                'host'   => '192.168.0.1',
                'port'  => '3306', // optional
                'reserveHost' => '192.168.0.1', // mandatory
                'reservePort' => '3308', // optional
                'user' => 'username', // if you need different username or password for reserve host use reserveUser and reservePassword
                'password' => "password",
                'dbname' => "test",
                'heartbeatTable' => 'heartbeat', // default value is 'heartbeat'
                'heartbeatTableColumn' => 'value', // default value is 'value'
                'dontRetryPeriod' => '600', // period between retries to failback to main host
                'wrapperClass' => '\DoctrineExtensions\DBAL\Connections\MasterMasterFailoverConnection', // mandatory
                'failoverStatusCacheImpl' => new \Doctrine\Common\Cache\ApcCache() // mandatory, should be instance of \Doctrine\Common\Cache
);

```

Failover-specific events
------------------------

Two new event's types were added 'onFailover', 'onFailback'.


