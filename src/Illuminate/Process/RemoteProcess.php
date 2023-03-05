<?php

namespace Illuminate\Process;

use Exception;
use Illuminate\Support\Arr;

class RemoteProcess
{
    protected array $extraOptions = [];

    public function __construct(protected string $user, protected string $host)
    {

    }

    public static function create(string $user, string $host)
    {
        return new static($user, $host);
    }

    public function usePrivateKey(string $pathToPrivateKey): self
    {
        $this->extraOptions['private_key'] = '-i ' . $pathToPrivateKey;

        return $this;
    }

    public function useJumpHost(string $jumpHost): self
    {
        $this->extraOptions['jump_host'] = '-J ' . $jumpHost;

        return $this;
    }

    public function usePort(int $port): self
    {
        if ($port < 0) {
            throw new Exception('Port must be a positive integer.');
        }

        if($port === 22){
            return $this;
        }

        $this->extraOptions['port'] = '-p ' . $port;

        return $this;
    }

    public function useMultiplexing(string $controlPath, string $controlPersist = '10m'): self
    {
        $this->extraOptions['control_master'] = '-o ControlMaster=auto -o ControlPath=' . $controlPath . ' -o ControlPersist=' . $controlPersist;

        return $this;
    }

    public function enableStrictHostKeyChecking(): self
    {
        unset($this->extraOptions['enable_strict_check']);

        return $this;
    }

    public function disableStrictHostKeyChecking(): self
    {
        $this->extraOptions['enable_strict_check'] = '-o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null';

        return $this;
    }

    public function enableQuietMode(): self
    {
        $this->extraOptions['quiet'] = '-q';

        return $this;
    }

    public function disableQuietMode(): self
    {
        unset($this->extraOptions['quiet']);

        return $this;
    }

    public function disablePasswordAuthentication(): self
    {
        $this->extraOptions['password_authentication'] = '-o PasswordAuthentication=no';

        return $this;
    }

    public function enablePasswordAuthentication(): self
    {
        unset($this->extraOptions['password_authentication']);

        return $this;
    }

    public function addExtraOption(string $option): self
    {
        $this->extraOptions[] = $option;

        return $this;
    }

    public function getCommand(string|array $command): string
    {
        $commands = Arr::wrap($command);

        $extraOptions = implode(' ', $this->getExtraOptions());

        $commandString = implode(PHP_EOL, $commands);

        $delimiter = 'EOF-LARAVEL-SSH';

        $target = $this->getTargetForSsh();

        if (in_array($this->host, ['local', 'localhost', '127.0.0.1'])) {
            return $commandString;
        }

        return "ssh {$extraOptions} {$target} 'bash -se' << \\$delimiter".PHP_EOL
            .$commandString.PHP_EOL
            .$delimiter;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getHost()
    {
        return $this->host;
    }

    private function getExtraOptions(): array
    {
        return array_values($this->extraOptions);
    }

    protected function getTargetForSsh(): string
    {
        return "{$this->user}@{$this->host}";
    }
}
