<?php
namespace DoctrineExtensions\DBAL\Connections;

use Doctrine\Common\Cache\Cache;
use Doctrine\DBAL\DBALException;

class FailoverStatus
{
    private $params;
    /**
     * @var Cache
     */
    private $cache;

    function __construct(array $params)
    {
        $this->params = $params;
        $this->cache  = $this->getCacheFromParams();
    }

    private function getCacheFromParams()
    {
        if(!isset($this->params['failoverStatusCacheImpl']) || !$this->params['failoverStatusCacheImpl'] instanceof Cache) {
            throw new DBALException('failoverStatusCacheImpl param should be set to valid cache implementation');
        }

        return $this->params['failoverStatusCacheImpl'];
    }

    public function update($seconds)
    {
        $this->cache->save($this->cacheVariableName(), time() + $seconds);
    }

    public function isClean()
    {
        return !$this->cache->contains($this->cacheVariableName());
    }

    public function isActive()
    {
        return !$this->isClean() && $this->cache->fetch($this->cacheVariableName()) > time();
    }

    public function clear()
    {
        $this->cache->delete($this->cacheVariableName());
    }

    private function cacheVariableName()
    {
        return $this->params['host'].':'.$this->params['port'].':failoverStatus';
    }


}
