<?php
namespace DoctrineExtensions\Tests\DBAL\Connections;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use DoctrineExtensions\DBAL\Connections\FailoverStatus;

class FailoverStatusTest extends \PHPUnit_Framework_TestCase
{
    const CACHE_VARIABLE_NAME = 'host:port:failoverStatus';

    /**
     * @var Cache
     */
    private $cache;

    protected function setUp()
    {
        $this->cache = new ArrayCache();
    }

    /**
     * @expectedException \Doctrine\DBAL\DBALException
     */
    public function test_if_cache_was_not_provided_then_exception_should_be_raised()
    {
        new FailoverStatus(array());
    }

    public function test_failover_status_states_transitions()
    {
        $status = $this->statusObject();
        $this->assertTrue($status->isClean());
        $this->assertFalse($status->isActive());

        $status->turnOnOrRefresh();

        $this->assertFalse($status->isClean());
        $this->assertTrue($status->isActive());

        $this->cache->save('host:port:failoverStatus', time() - 10);

        $this->assertFalse($status->isClean());
        $this->assertFalse($status->isActive());

        $status->clear();

        $this->assertTrue($status->isClean());
        $this->assertFalse($status->isActive());
    }

    public function test_if_no_dont_retry_period_option_was_provided_then_default_should_be_used()
    {
        $status = $this->statusObject();
        $status->turnOnOrRefresh();

        $this->assertExpectedEndOfPeriodEquals(time() + FailoverStatus::DEFAULT_DONT_RETRY_PERIOD);
    }

    public function test_if_dont_retry_period_was_provided_then_it_should_be_used_instead_of_default()
    {
        $status = $this->statusObject(30);
        $status->turnOnOrRefresh();

        $this->assertExpectedEndOfPeriodEquals(time() + 30);
    }

    private function statusObject($dontRetryPeriod = null)
    {
        $params = array('failoverStatusCacheImpl' => $this->cache, 'host' => 'host', 'port' => 'port');

        if($dontRetryPeriod !== null) {
            $params['dontRetryPeriod'] = $dontRetryPeriod;
        }

        return new FailoverStatus($params);
    }

    private function assertExpectedEndOfPeriodEquals($expectedEndOfPeriod)
    {
        $this->assertEquals($expectedEndOfPeriod, $this->cache->fetch(self::CACHE_VARIABLE_NAME),
            'Not expected retry period', 1);
    }


}