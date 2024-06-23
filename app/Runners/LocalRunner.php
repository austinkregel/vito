<?php

namespace App\Runners;

use App\Contracts\Services\RunnerContract;

class LocalRunner extends AbstractRunner
{
    public function connect(): RunnerContract
    {
        return $this;
    }

    public function exec(string $command, string $log = '', int $siteId = null, ?bool $stream = false): string
    {
        $output = shell_exec($command);

        if ($log) {
            file_put_contents($log, $output);
        }

        return $output;
    }
}
