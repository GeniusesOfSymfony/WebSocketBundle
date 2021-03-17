<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Topic;

use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Ratchet\Wamp\Topic;

trigger_deprecation('gos/web-socket-bundle', '3.7', 'The "%s" interface is deprecated and will be removed in 4.0, use the symfony/messenger component instead.', PushableTopicInterface::class);

/**
 * @deprecated to be removed in 4.0, use the symfony/messenger component instead
 */
interface PushableTopicInterface
{
    /**
     * @param string|array $data
     */
    public function onPush(Topic $topic, WampRequest $request, $data, string $provider): void;
}
