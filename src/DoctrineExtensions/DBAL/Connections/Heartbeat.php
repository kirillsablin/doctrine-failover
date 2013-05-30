<?php
namespace DoctrineExtensions\DBAL\Connections;

use Doctrine\DBAL\Driver\Connection;

interface Heartbeat
{
    public function isReplicationAlive(Connection $main, Connection $reserve);
}