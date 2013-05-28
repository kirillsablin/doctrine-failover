<?php
namespace DoctrineExtensions\DBAL\Connections;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Connection;

class Heartbeat
{
    const WRITE_HEARTBEAT_TOKEN_SQL      = "INSERT INTO `%s` (`%s`) values(?)";
    const READ_HEARTBEAT_TOKEN_SQL       = "SELECT * FROM `%s` WHERE `%s` = ?";
    const DEFAULT_HEARTBEAT_TABLE        = "heartbeat";
    const DEFAULT_HEARTBEAT_TABLE_COLUMN = "value";

    private $heartbeatToken;
    private $heartbeatTable;
    private $heartbeatTableColumn;

    public function __construct(array $params)
    {
        $this->heartbeatTable       = isset($params['heartbeatTable']) ? $params['heartbeatTable'] : self::DEFAULT_HEARTBEAT_TABLE;
        $this->heartbeatTableColumn = isset($params['heartbeatTableColumn']) ? $params['heartbeatTableColumn'] : self::DEFAULT_HEARTBEAT_TABLE_COLUMN;

        $this->heartbeatToken = $this->newHeartbeatToken();
    }

    public function startCycle(Connection $db)
    {
        $insert_stmt = $db->prepare(sprintf(self::WRITE_HEARTBEAT_TOKEN_SQL, $this->heartbeatTable, $this->heartbeatTableColumn));
        if(!$insert_stmt->execute(array($this->heartbeatToken))) {
            throw new DBALException("Error during starting heartbeat cycle");
        }
    }

    public function listenForEcho(Connection $db)
    {
        $read_stmt = $db->prepare(sprintf(self::READ_HEARTBEAT_TOKEN_SQL, $this->heartbeatTable, $this->heartbeatTableColumn));
        $read_stmt->execute(array($this->heartbeatToken));

        if($read_stmt->fetch() === false) {
            throw new DBALException("Error during listening for heartbeat echo");
        }
    }

    private function newHeartbeatToken()
    {
        return md5(rand(0, 10000000)).time();
    }

}
