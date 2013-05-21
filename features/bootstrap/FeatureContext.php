<?php

use Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;
use FailoverContext\SandboxController;
use Doctrine\DBAL\DriverManager;
use Doctrine\Common\Cache\ArrayCache;

require_once __DIR__."/bootstrap.php";

class FeatureContext extends BehatContext
{
    private $sandboxController;
    private $dbParams;
    /**
     * @var DoctrineExtensions\DBAL\Connections\MasterMasterFailoverConnection
     */
    private $connection;
    /**
     * @var Doctrine\Common\Cache\ArrayCache
     */
    private $cache;

    public function __construct(array $parameters)
    {
        $this->sandboxController = new SandboxController($parameters['sandbox_dir']);
        $this->cache             = new ArrayCache();

        $this->dbParams                            = $parameters['db'];
        $this->dbParams['wrapperClass']            = '\DoctrineExtensions\DBAL\Connections\MasterMasterFailoverConnection';
        $this->dbParams['failoverStatusCacheImpl'] = $this->cache;

        $this->connection = DriverManager::getConnection($this->dbParams);
    }

    /**
     * @Given /^main db is online$/
     */
    public function mainDbIsOnline()
    {
        $this->sandboxController->startAllServers();
    }

    /**
     * @Given /^failover status is clean$/
     */
    public function failoverStatusIsClean()
    {
        $this->cache->delete($this->failoverStatusVar());
    }

    private function failoverStatusVar()
    {
        return $this->dbParams['host'].':'.$this->dbParams['port'].':failoverStatus';
    }

    /**
     * @When /^I connect to db$/
     */
    public function iConnectToDb()
    {
        $this->connection->connect();
    }

    /**
     * @Then /^main db should be used$/
     */
    public function mainDbShouldBeUsed()
    {
        \assertEquals($this->dbParams['port'], $this->connection->getPort());
        \assertEquals($this->dbParams['host'], $this->connection->getHost());
    }

    /**
     * @Given /^main db is offline$/
     */
    public function mainDbIsOffline()
    {
        $this->sandboxController->stopFirstServer();
    }

    /**
     * @Then /^failover status should be set to use reserve and dont retry until some time in future$/
     */
    public function failoverStatusShouldBeSetToUseReserveAndDontRetryUntilSomeTimeInFuture()
    {
        \assertTrue($this->cache->fetch($this->failoverStatusVar()) > time());
    }

    /**
     * @Given /^reserve db should be used$/
     */
    public function reserveDbShouldBeUsed()
    {
        \assertEquals($this->dbParams['reserve_port'], $this->connection->getPort());
        \assertEquals($this->dbParams['reserve_host'], $this->connection->getHost());
    }

    /**
     * @Given /^failover status is dont retry until future$/
     */
    public function failoverStatusIsDontRetryUntilFuture()
    {
        $this->cache->save($this->failoverStatusVar(), time() + 600);
    }

}
