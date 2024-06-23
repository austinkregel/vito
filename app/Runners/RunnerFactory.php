<?php

namespace App\Runners;

use App\Contracts\Services\RunnerContract;
use App\Facades\SSH;
use App\Models\Server;

class RunnerFactory
{
    public function make(Server $server, ?string $user = null): RunnerContract
    {
        if ($this->isServerVitoIsOn($server)) {
            return new LocalRunner($server, $user);
        }
        // At the moment we only connect via ssh, but this will be expanded upon soon.
        return SSH::init($server, $user);
    }

    protected function isServerVitoIsOn(Server $server): bool
    {
        return in_array($server->ip, [

        ]);
    }
}
