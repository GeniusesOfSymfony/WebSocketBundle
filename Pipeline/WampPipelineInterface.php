<?php

namespace Gos\Bundle\WebSocketBundle\Pipeline;

use Ratchet\Wamp\WampServerInterface;

interface WampPipelineInterface
{
    const SUBSCRIPTION = 'onSubscribe';
    const UNSUBSCRIPTION = 'onUnSubscribe';
    const PUBLICATION = 'onPublish';

    /**
     * @return PipeInterface
     */
    public function pipe();

    /**
     * @param WampServerInterface $wampApplication
     */
    public function setWampApplication(WampServerInterface $wampApplication);

    public function flush();
}