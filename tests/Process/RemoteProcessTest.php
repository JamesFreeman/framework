<?php

namespace Illuminate\Tests\Process;

use Exception;
use Illuminate\Process\RemoteProcess;
use PHPUnit\Framework\TestCase;

class RemoteProcessTest extends TestCase
{
    public function testThatYouCanCreateARemoteProcess()
    {
        $remoteProcess = RemoteProcess::create('foo', 'bar');
        $command = 'ls -al';

        $this->assertInstanceOf(RemoteProcess::class, $remoteProcess);
        $this->assertEquals('foo', $remoteProcess->getUser());
        $this->assertEquals('bar', $remoteProcess->getHost());
        $this->assertEquals($this->getExpectedCommand($command), $remoteProcess->getCommand($command));
    }

    public function testThatYouCanCreateACommandWithAnArray()
    {
        $remoteProcess = RemoteProcess::create('foo', 'bar');
        $command = ['ls -al', 'whoami'];

        $this->assertInstanceOf(RemoteProcess::class, $remoteProcess);
        $this->assertEquals('foo', $remoteProcess->getUser());
        $this->assertEquals('bar', $remoteProcess->getHost());
        $this->assertEquals($this->getExpectedCommand("ls -al\nwhoami"), $remoteProcess->getCommand($command));
    }

    /** @dataProvider localAddresses */
    public function testThatIfYouTryToSSHLocallyItWillGiveDefaultCommand(string $host)
    {
        $remoteProcess = RemoteProcess::create('foo', $host);
        $command = 'ls -al';

        $this->assertInstanceOf(RemoteProcess::class, $remoteProcess);
        $this->assertEquals($host, $remoteProcess->getHost());
        $this->assertEquals($command, $remoteProcess->getCommand($command));
    }

    public function testThatYouCanCreateARemoteProcessWithADifferentPort()
    {
        $remoteProcess = RemoteProcess::create('foo', 'bar')
            ->usePort(222);
        $command = 'ls -al';

        $this->assertInstanceOf(RemoteProcess::class, $remoteProcess);
        $this->assertEquals($this->getExpectedCommand($command, '-p 222'), $remoteProcess->getCommand($command));
    }

    public function testThatYouCanCreateARemoteProcessWithPort22()
    {
        $remoteProcess = RemoteProcess::create('foo', 'bar')
            ->usePort(22);
        $command = 'ls -al';

        $this->assertInstanceOf(RemoteProcess::class, $remoteProcess);
        $this->assertEquals($this->getExpectedCommand($command), $remoteProcess->getCommand($command));
    }

    public function testThatYouCannotCreateARemoteProcessWithANegativePort()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Port must be a positive integer.');

        RemoteProcess::create('foo', 'bar')->usePort(-5);
    }

    public function testThatYouCanCreateARemoteProcessWithAPrivateKey()
    {
        $remoteProcess = RemoteProcess::create('foo', 'bar')
            ->usePrivateKey('~/.ssh/github');
        $command = 'ls -al';

        $this->assertInstanceOf(RemoteProcess::class, $remoteProcess);
        $this->assertEquals($this->getExpectedCommand($command, '-i ~/.ssh/github'), $remoteProcess->getCommand($command));
    }

    public function testThatYouCanCreateARemoteProcessWithAJumpHost()
    {
        $remoteProcess = RemoteProcess::create('foo', 'bar')
            ->useJumpHost('foo');
        $command = 'ls -al';

        $this->assertInstanceOf(RemoteProcess::class, $remoteProcess);
        $this->assertEquals($this->getExpectedCommand($command, '-J foo'), $remoteProcess->getCommand($command));
    }

    public function testThatYouCanCreateARemoteProcessUsingMultiplexing()
    {
        $remoteProcess = RemoteProcess::create('foo', 'bar')
            ->useMultiplexing('foo');
        $command = 'ls -al';

        $this->assertInstanceOf(RemoteProcess::class, $remoteProcess);
        $this->assertEquals($this->getExpectedCommand($command, '-o ControlMaster=auto -o ControlPath=foo -o ControlPersist=10m'), $remoteProcess->getCommand($command));
    }

    public function testThatYouCanDisableStrictHostKeyChecking()
    {
        $remoteProcess = RemoteProcess::create('foo', 'bar')
            ->disableStrictHostKeyChecking();
        $command = 'ls -al';

        $this->assertInstanceOf(RemoteProcess::class, $remoteProcess);
        $this->assertEquals($this->getExpectedCommand($command, '-o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null'), $remoteProcess->getCommand($command));
    }

    public function testThatYouCanEnableQuietMode()
    {
        $remoteProcess = RemoteProcess::create('foo', 'bar')
            ->enableQuietMode();
        $command = 'ls -al';

        $this->assertInstanceOf(RemoteProcess::class, $remoteProcess);
        $this->assertEquals($this->getExpectedCommand($command, '-q'), $remoteProcess->getCommand($command));
    }

    public function testThatYouCanDisablePasswordAuthentication()
    {
        $remoteProcess = RemoteProcess::create('foo', 'bar')
            ->disablePasswordAuthentication();
        $command = 'ls -al';

        $this->assertInstanceOf(RemoteProcess::class, $remoteProcess);
        $this->assertEquals($this->getExpectedCommand($command, '-o PasswordAuthentication=no'), $remoteProcess->getCommand($command));
    }

    public function testThatYouCanRemoveQuietMode()
    {
        $remoteProcess = RemoteProcess::create('foo', 'bar')
            ->enableQuietMode()
            ->disableQuietMode();

        $command = 'ls -al';

        $this->assertInstanceOf(RemoteProcess::class, $remoteProcess);
        $this->assertEquals($this->getExpectedCommand($command), $remoteProcess->getCommand($command));
    }

    public function testThatYouCanEnableStrictHostKeyChecking()
    {
        $remoteProcess = RemoteProcess::create('foo', 'bar')
            ->disableStrictHostKeyChecking()
            ->enableStrictHostKeyChecking();

        $command = 'ls -al';

        $this->assertInstanceOf(RemoteProcess::class, $remoteProcess);
        $this->assertEquals($this->getExpectedCommand($command), $remoteProcess->getCommand($command));
    }

    private function getExpectedCommand(array|string $command, $extrasString = '')
    {
        $command = implode(PHP_EOL, is_array($command) ? $command : [$command]);

        return "ssh {$extrasString} foo@bar 'bash -se' << \EOF-LARAVEL-SSH
$command
EOF-LARAVEL-SSH";
    }

    public function localAddresses()
    {
        return [
            ['127.0.0.1'],
            ['localhost'],
            ['local'],
        ];
    }
}
