<?php

namespace App\Helpers;

use App\Contracts\Services\RunnerContract;
use App\Exceptions\SSHAuthenticationError;
use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHConnectionError;
use App\Models\Server;
use App\Models\ServerLog;
use App\Runners\SSHRunner;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use phpseclib3\Crypt\Common\PrivateKey;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;
use Throwable;

class SSH
{
    public function init(Server $server, ?string $asUser = null): RunnerContract
    {
        return new SSHRunner(
            server: $server->refresh(),
            privateKey: PublicKeyLoader::loadPrivateKey(
                file_get_contents($server->sshKey()['private_key_path'])
            ),
            user: $asUser && $asUser !== $server->getSshUser() ? $asUser : $server->getSshUser(),
        );
    }
}
