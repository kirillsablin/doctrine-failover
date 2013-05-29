<?php
namespace DoctrineExtensions\DBAL\Connections;

use Doctrine\Common\Cache\Cache;
use Doctrine\DBAL\DBALException;

class FailoverStatus
{
    const DEFAULT_DONT_RETRY_PERIOD = 900;

    private $params;
    /**
     * @var Cache
     */
    private $cache;

    private $dontRetryPeriod;

    function __construct(array $params)
    {
        $this->params          = $params;
        $this->cache           = $this->getCacheFromParams();
        $this->dontRetryPeriod = isset($this->params['dontRetryPeriod']) ? $this->params['dontRetryPeriod'] : self::DEFAULT_DONT_RETRY_PERIOD;
    }

    private function getCacheFromParams()
    {
        if(!isset($this->params['failoverStatusCacheImpl']) || !$this->params['failoverStatusCacheImpl'] instanceof Cache) {
            throw new DBALException('failoverStatusCacheImpl param should be set to valid cache implementation');
        }

        return $this->params['failoverStatusCacheImpl'];
    }

    public function turnOnOrRefresh()
    {
        $this->cache->save($this->cacheVariableName(), time() + $this->dontRetryPeriod);
    }

    public function clear()
    {
        $this->cache->delete($this->cacheVariableName());
    }

    public function isClean()
    {
        return !$this->cache->contains($this->cacheVariableName());
    }

    public function isActive()
    {
        return !$this->isClean() && $this->cache->fetch($this->cacheVariableName()) > time();
    }

    private function cacheVariableName()
    {
        return $this->params['host'].':'.$this->params['port'].':failoverStatus';
    }


}
