<?php
namespace DoctrineExtensions\DBAL\Connections;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Connection;

class TableHeartbeat implements Heartbeat
{
    const WRITE_HEARTBEAT_TOKEN_SQL      = "INSERT INTO `%s` (`%s`) values(?)";
    const READ_HEARTBEAT_TOKEN_SQL       = "SELECT * FROM `%s` WHERE `%s` = ?";
    const DEFAULT_HEARTBEAT_TABLE        = "heartbeat";
    const DEFAULT_HEARTBEAT_TABLE_COLUMN = "value";
    const DEFAULT_DELAY_BEFORE_LISTENING = 10000;

    private $heartbeatToken;
    private $heartbeatTable;
    private $heartbeatTableColumn;
    private $delayBeforeListening;

    public function __construct(array $params)
    {
        $this->heartbeatTable       = isset($params['heartbeatTable']) ? $params['heartbeatTable'] : self::DEFAULT_HEARTBEAT_TABLE;
        $this->heartbeatTableColumn = isset($params['heartbeatTableColumn']) ? $params['heartbeatTableColumn'] : self::DEFAULT_HEARTBEAT_TABLE_COLUMN;
        $this->delayBeforeListening = isset($params['delayBeforeListening']) ? $params['delayBeforeListening'] : self::DEFAULT_DELAY_BEFORE_LISTENING;

        $this->heartbeatToken = $this->newHeartbeatToken();
    }

    private function newHeartbeatToken()
    {
        return md5(rand(0, 10000000)).time();
    }

    public function isReplicationAlive(Connection $main, Connection $reserve)
    {
        try {
            $this->startCycle($reserve);
            \usleep($this->delayBeforeListening);
            $this->listenForEcho($main);
        }
        catch(DBALException $e) {

            return false;
        }

        return true;
    }


    private function startCycle(Connection $db)
    {
        $insertStatement = $db->prepare(sprintf(self::WRITE_HEARTBEAT_TOKEN_SQL, $this->heartbeatTable, $this->heartbeatTableColumn));
        if(!$insertStatement->execute(array($this->heartbeatToken))) {
            throw new DBALException("Error during starting heartbeat cycle");
        }
    }

    private function listenForEcho(Connection $db)
    {
        $readStatement = $db->prepare(sprintf(self::READ_HEARTBEAT_TOKEN_SQL, $this->heartbeatTable, $this->heartbeatTableColumn));
        $readStatement->execute(array($this->heartbeatToken));

        if($readStatement->fetch() === false) {
            throw new DBALException("Error during listening for heartbeat echo");
        }
    }

}
