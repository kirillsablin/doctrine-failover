<?php
namespace Doctrine\Tests\DBAL\Functional\Failover;

use Doctrine\Tests\DbalFunctionalTestCase;

class MasterMasterFailoverConnectionTest extends DbalFunctionalTestCase
{
    public function test_dummy()
    {
        $s = new SandboxController('/home/kirk/sandboxes/rcsandbox_5_1_61');
        $s->stopSlaveAtFirstServer();
        $s->resumeCircularReplication();
    }

}
