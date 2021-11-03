<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Server\Type;

interface ServerInterface
{
    public function launch(string $host, int $port, bool $profile);

    public function getName(): string;
}
