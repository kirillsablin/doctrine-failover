<?php
namespace DoctrineExtensions\DBAL\Connections;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Configuration;
use Doctrine\Common\EventManager;

class MasterMasterFailoverConnection extends Connection
{

    private $isConnected = false;
    private $usedParams = null;

    private $failoverStatus;

    private $heartbeat;

    public function __construct(array $params, Driver $driver, Configuration $config = null, EventManager $eventManager = null)
    {
        $this->failoverStatus = new FailoverStatus($params);
        $this->heartbeat      = new TableHeartbeat($params);
        parent::__construct($params, $driver, $config, $eventManager);
    }

    public function connect()
    {
        if($this->isConnected) {
            return false;
        }

        if($this->failoverStatus->isClean()) {
            $this->connectWithFailover();
        }
        elseif($this->failoverStatus->isActive()) {
            $this->connectToReserve();
        }
        elseif($this->canSwitchBackToMain()) {
            $this->failoverStatus->clear();
            $this->connectWithFailover();
        }
        else {
            $this->connectToReserve();
            $this->failoverStatus->turnOnOrRefresh();
        }

        $this->isConnected = true;

        if($this->_eventManager->hasListeners(Events::postConnect)) {
            $eventArgs = new ConnectionEventArgs($this);
            $this->_eventManager->dispatchEvent(Events::postConnect, $eventArgs);
        }

        return true;
    }

    public function isConnected()
    {
        return $this->isConnected;
    }

    private function connectWithFailover()
    {
        try {
            $this->connectToMain();
        }
        catch(\Exception $e) {
            $this->connectToReserve();
            $this->failoverStatus->turnOnOrRefresh();
        }
    }

    private function connectToMain()
    {
        return $this->connectByParams($this->getParams());
    }

    private function connectToReserve()
    {
        return $this->connectByParams($this->reserveParams());
    }

    private function connectByParams(array $params)
    {
        $this->usedParams = $params;

        $driverOptions = isset($params['driverOptions']) ? $params['driverOptions'] : array();

        $user     = isset($params['user']) ? $params['user'] : null;
        $password = isset($params['password']) ? $params['password'] : null;

        return $this->_driver->connect($params, $user, $password, $driverOptions);
    }

    private function reserveParams()
    {
        $params = $this->getParams();

        foreach($params as $paramName => $paramValue) {
            if($this->isReserveParam($paramName)) {
                $params[$this->convertReserveParamToNormal($paramName)] = $paramValue;
            }
        }

        return $params;
    }

    private function isReserveParam($paramName)
    {
        return \strpos($paramName, 'reserve') === 0;
    }

    private function convertReserveParamToNormal($paramName)
    {
        return \strtolower($paramName[7]).\substr($paramName, 8);
    }

    private function canSwitchBackToMain()
    {
        return $this->heartbeat->isReplicationAlive($this->connectToMain(), $this->connectToReserve());
    }

    public function getHost()
    {
        if(empty($this->usedParams)) {
            return parent::getHost();
        }
        else {
            return isset($this->usedParams['host']) ? $this->usedParams['host'] : null;
        }
    }

    public function getPort()
    {
        if(empty($this->usedParams)) {
            return parent::getPort();
        }
        else {
            return isset($this->usedParams['port']) ? $this->usedParams['port'] : null;
        }
    }

}
