<?php

namespace App\Runners;

use App\Contracts\Services\RunnerContract;
use App\Models\Server;

abstract class AbstractRunner implements RunnerContract
{
    public function __construct(
        protected Server $server,
        protected ?string $user = null,
    ) {
    }
}
