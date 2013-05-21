<?php
namespace FailoverContext;

class SandboxController
{
    private $pathToSandbox;

    public function __construct($path_to_sandbox)
    {
        $this->pathToSandbox = $path_to_sandbox;
    }

    public function startAllServers()
    {
        $this->executeCommandAndEnsureReturn('start_all', 'sandbox server started', 2);
    }

    public function stopFirstServer()
    {
        $this->executeCommand('node1/stop');
        $this->executeCommandAndEnsureReturn('node1/status', 'node1 off');
    }

    public function startFirstServer()
    {
        $this->executeCommandAndEnsureReturn('node1/start', 'sandbox server started');
    }

    public function stopSlaveAtFirstServer()
    {
        $this->executeCommand("node1/use -u root -e  'stop slave'");
        $this->executeCommandAndEnsureReturn("./node1/use -u root -e  'show slave status'", 'Yes', 0);
    }

    public function resumeCircularReplication()
    {
        $this->executeCommand("node1/use -u root -e  'start slave'");
        $this->executeCommandAndEnsureReturn("./node1/use -u root -e  'show slave status'", 'Yes', 2);
    }

    private function executeCommandAndEnsureReturn($command, $expected_text_in_return, $count = 1)
    {
        $output = $this->executeCommand($command);
        $this->ensureTextInOutput($output, $expected_text_in_return, $count);
    }

    private function executeCommand($command)
    {
        return \shell_exec($this->pathToSandbox.'/'.$command);
    }

    private function ensureTextInOutput($output, $text, $times = 1)
    {
        if(\substr_count($output, $text) != $times) {
            throw new \RuntimeException("Unexpected output: \n".$output);
        }
    }

}
