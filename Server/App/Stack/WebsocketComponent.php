<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Stack;

use Ratchet\WebSocket\Encoding\ToggleableValidator;
use Ratchet\WebSocket\VersionManager;
use Ratchet\WebSocket\Version;

class WebsocketComponent
{
    /** @var VersionManager  */
    protected $versionManager;

    /** @var ToggleableValidator  */
    protected $validator;

    /** @var ConnectionStorageInterface  */
    protected $connectionStorage;

    /**
     * @param ConnectionStorageInterface $connectionStorage
     */
    public function __construct(ConnectionStorageInterface $connectionStorage)
    {
        $this->versionManager = new VersionManager();
        $this->validator = new ToggleableValidator();
        $this->connectionStorage = $connectionStorage;

        $this->versionManager
            ->enableVersion(new Version\RFC6455($this->validator))
            ->enableVersion(new Version\HyBi10($this->validator))
            ->enableVersion(new Version\Hixie76())
        ;
    }
}
