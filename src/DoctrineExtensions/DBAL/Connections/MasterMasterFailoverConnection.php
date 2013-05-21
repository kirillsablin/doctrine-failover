<?php
namespace DoctrineExtensions\DBAL\Connections;

use Doctrine\DBAL\Connection;

class MasterMasterFailoverConnection extends Connection
{
    public function getCurrentServer()
    {
        return 'main';
    }
}
