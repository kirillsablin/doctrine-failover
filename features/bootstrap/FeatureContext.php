<?php

use Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;
use FailoverContext\SandboxController;
use Doctrine\DBAL\DriverManager;

require_once __DIR__."/bootstrap.php";

class FeatureContext extends BehatContext
{
    private $sandboxController;
    private $dbParams;
    /**
     * @var DoctrineExtensions\DBAL\Connections\MasterMasterFailoverConnection
     */
    private $connection;

    public function __construct(array $parameters)
    {
        $this->sandboxController        = new SandboxController($parameters['sandbox_dir']);
        $this->dbParams                 = $parameters['db'];
        $this->dbParams['wrapperClass'] = '\DoctrineExtensions\DBAL\Connections\MasterMasterFailoverConnection';
        $this->connection               = DriverManager::getConnection($this->dbParams);
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
        \assertEquals('main', $this->connection->getCurrentServer());
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
        throw new PendingException();
    }

    /**
     * @Given /^reserve db should be used$/
     */
    public function reserveDbShouldBeUsed()
    {
        \assertEquals('reserve', $this->connection->getCurrentServer());
    }

}
