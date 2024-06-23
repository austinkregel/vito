<?php

namespace App\Runners;

use App\Contracts\Services\RunnerContract;
use App\Exceptions\SSHAuthenticationError;
use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHConnectionError;
use App\Facades\SSH;
use App\Models\Server;
use App\Models\ServerLog;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;

class SSHRunner extends AbstractRunner
{
    public function __construct(
        Server $server,
        protected PrivateKey $privateKey,
        ?string $user = null,
        public ?ServerLog $log = null,
        protected SSH2|SFTP|null $connection = null,
        protected ?string $publicKey = null,
    )
    {
        parent::__construct($server, $user);
    }

    public function setLog(ServerLog $log): self
    {
        $this->log = $log;

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function connect(bool $sftp = false): static
    {
        // If the IP is an IPv6 address, we need to wrap it in square brackets
        $ip = $this->server->ip;
        if (str($ip)->contains(':')) {
            $ip = '['.$ip.']';
        }
        try {
            if ($sftp) {
                $this->connection = new SFTP($ip, $this->server->port);
            } else {
                $this->connection = new SSH2($ip, $this->server->port);
            }

            $login = $this->connection->login($this->user, $this->privateKey);

            if (! $login) {
                throw new SSHAuthenticationError('Error authenticating');
            }

            return $this;
        } catch (Throwable $e) {
            Log::error('Error connecting', [
                'msg' => $e->getMessage(),
            ]);
            throw new SSHConnectionError($e->getMessage());
        }
    }

    /**
     * @throws SSHCommandError
     * @throws SSHConnectionError
     */
    public function exec(string $command, string $log = '', ?int $siteId = null, ?bool $stream = false): string
    {
        if (! $this->log && $log) {
            $this->log = ServerLog::make($this->server, $log);
            if ($siteId) {
                $this->log->forSite($siteId);
            }
            $this->log->save();
        }

        try {
            if (! $this->connection) {
                $this->connect();
            }
        } catch (Throwable $e) {
            throw new SSHConnectionError($e->getMessage());
        }

        try {
            if ($this->asUser) {
                $command = 'sudo su - '.$this->asUser.' -c '.'"'.addslashes($command).'"';
            }

            $this->connection->setTimeout(0);
            if ($stream) {
                $this->connection->exec($command, function ($output) {
                    $this->log?->write($output);
                    echo $output;
                    ob_flush();
                    flush();
                });

                return '';
            } else {
                $output = $this->connection->exec($command);

                $this->log?->write($output);

                if ($this->connection->getExitStatus() !== 0 || Str::contains($output, 'VITO_SSH_ERROR')) {
                    throw new SSHCommandError('SSH command failed with an error', $this->connection->getExitStatus());
                }

                return $output;
            }
        } catch (Throwable $e) {
            throw $e;
            throw new SSHCommandError($e->getMessage());
        }
    }

    /**
     * @throws Throwable
     */
    public function upload(string $local, string $remote): void
    {
        $this->log = null;

        if (! $this->connection) {
            $this->connect(true);
        }
        $this->connection->put($remote, $local, SFTP::SOURCE_LOCAL_FILE);
    }

    /**
     * @throws Exception
     */
    public function disconnect(): void
    {
        if ($this->connection) {
            $this->connection->disconnect();
            $this->connection = null;
        }
    }

    /**
     * @throws Exception
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
