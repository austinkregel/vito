<?php

namespace App\Contracts\Services;

use App\Models\ServerLog;

interface RunnerContract
{
    public function connect(): RunnerContract;

    public function exec(string $command, string $log = '', int $siteId = null, ?bool $stream = false): string;
    public function setLog(ServerLog $log): RunnerContract;
    public function upload(string $local, string $remote): void;
    public function disconnect(): void;
}
