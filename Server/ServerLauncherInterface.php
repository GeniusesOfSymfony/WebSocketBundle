<?php

namespace Gos\Bundle\WebSocketBundle\Server;

interface ServerLauncherInterface
{
    public function launch(?string $serverName, string $host, int $port, bool $profile): void;
}
