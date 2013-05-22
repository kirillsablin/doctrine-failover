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
    const DONT_RETRY_PERIOD = 900;

    private $isConnected = false;
    private $usedParams = null;

    private $failoverStatus;

    public function __construct(array $params, Driver $driver, Configuration $config = null, EventManager $eventManager = null)
    {
        $this->failoverStatus = new FailoverStatus($params);
        parent::__construct($params, $driver, $config, $eventManager);
    }

    public function isConnected()
    {
        return $this->isConnected;
    }

    public function connect()
    {
        if($this->isConnected) {
            return false;
        }

        if($this->failoverStatus->isClean()) {
            try {
                $this->connectByParams($this->getParams());
            }
            catch(\Exception $e) {
                $this->connectByParams($this->reserveParams());
                $this->failoverStatus->update(self::DONT_RETRY_PERIOD);
            }
        }
        else {
            if($this->failoverStatus->isActive()) {
                $this->connectByParams($this->reserveParams());
            }
        }

        $this->isConnected = true;

        if($this->_eventManager->hasListeners(Events::postConnect)) {
            $eventArgs = new ConnectionEventArgs($this);
            $this->_eventManager->dispatchEvent(Events::postConnect, $eventArgs);
        }

        return true;
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

        foreach($params as $param_name => $param_value) {
            if(\strpos($param_name, 'reserve_') === 0) {
                $params[\substr($param_name, 8)] = $param_value;
            }
        }

        return $params;
    }

}
