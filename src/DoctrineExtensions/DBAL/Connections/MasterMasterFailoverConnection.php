<?php
namespace DoctrineExtensions\DBAL\Connections;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\Common\Cache\Cache;
use Doctrine\DBAL\DBALException;

class MasterMasterFailoverConnection extends Connection
{
    const DONT_RETRY_PERIOD = 900;

    private $isConnected = false;
    private $usedParams = null;

    public function isConnected()
    {
        return $this->isConnected;
    }

    public function connect()
    {
        if($this->isConnected) {
            return false;
        }
        $this->ensureValidParams();

        if($this->failoverStatus() !== false) {
            if($this->failoverStatus() > time()) {
                $this->connectByParams($this->reserveParams());
            }

        }
        else {
            try {
                $this->connectByParams($this->getParams());
            }
            catch(\Exception $e) {
                $this->connectByParams($this->reserveParams());
                $this->updateFailoverStatus();
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

    private function ensureValidParams()
    {
        $params = $this->getParams();
        if(!isset($params['failoverStatusCacheImpl']) || !$params['failoverStatusCacheImpl'] instanceof Cache) {
            throw new DBALException('failoverStatusCacheImpl param should be set to valid cache implementation');
        }
    }

    private function updateFailoverStatus()
    {
        $params = $this->getParams();
        $params['failoverStatusCacheImpl']->save($this->failoverStatusVar(), time() + self::DONT_RETRY_PERIOD);
    }

    private function failoverStatus()
    {
        $params = $this->getParams();

        return $params['failoverStatusCacheImpl']->fetch($this->failoverStatusVar());
    }

    private function failoverStatusVar()
    {
        $params = $this->getParams();

        return $params['host'].':'.$params['port'].':failoverStatus';
    }


}
