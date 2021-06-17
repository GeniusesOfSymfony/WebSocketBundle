<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle;

use Gos\Bundle\WebSocketBundle\Event\ClientConnectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientDisconnectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent;
use Gos\Bundle\WebSocketBundle\Event\ConnectionRejectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ServerLaunchedEvent;

trigger_deprecation('gos/web-socket-bundle', '4.0', 'The "%s" class is deprecated and will be removed in 5.0, register event listeners using event class names instead.', GosWebSocketEvents::class);

/**
 * @deprecated to be removed in 5.0, register event listeners using event class names instead.
 */
final class GosWebSocketEvents
{
    /**
     * The SERVER_LAUNCHED event occurs when a websocket server is launched.
     *
     * This event allows you to add services to the event loop for the server.
     *
     * @Event("Gos\Bundle\WebSocketBundle\Event\ServerLaunchedEvent")
     *
     * @deprecated to be removed in 5.0, register event listeners using the `Gos\Bundle\WebSocketBundle\Event\ServerLaunchedEvent` class instead.
     */
    public const SERVER_LAUNCHED = 'gos_web_socket.server_launched';

    /**
     * The CLIENT_CONNECTED event occurs when a client connects to a websocket server.
     *
     * @Event("Gos\Bundle\WebSocketBundle\Event\ClientConnectedEvent")
     *
     * @deprecated to be removed in 5.0, register event listeners using the `Gos\Bundle\WebSocketBundle\Event\ClientConnectedEvent` class instead.
     */
    public const CLIENT_CONNECTED = 'gos_web_socket.client_connected';

    /**
     * The CLIENT_DISCONNECTED event occurs when a client disconnects from a websocket server.
     *
     * @Event("Gos\Bundle\WebSocketBundle\Event\ClientDisconnectedEvent")
     *
     * @deprecated to be removed in 5.0, register event listeners using the `Gos\Bundle\WebSocketBundle\Event\ClientDisconnectedEvent` class instead.
     */
    public const CLIENT_DISCONNECTED = 'gos_web_socket.client_disconnected';

    /**
     * The CLIENT_ERROR event occurs when a client connection receives an error from a websocket server.
     *
     * This event allows you to add extra error handling within your application.
     *
     * @Event("Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent")
     *
     * @deprecated to be removed in 5.0, register event listeners using the `Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent` class instead.
     */
    public const CLIENT_ERROR = 'gos_web_socket.client_error';

    /**
     * The CONNECTION_REJECTED event occurs when a connection is rejected.
     *
     * @Event("Gos\Bundle\WebSocketBundle\Event\ConnectionRejectedEvent")
     */
    public const CONNECTION_REJECTED = 'gos_web_socket.connection_rejected';

    /**
     * Event aliases.
     *
     * These aliases are consumed by RegisterListenersPass.
     *
     * @deprecated to be removed in 5.0, register event listeners using event class names instead.
     */
    public const ALIASES = [
        ServerLaunchedEvent::class => self::SERVER_LAUNCHED,
        ClientConnectedEvent::class => self::CLIENT_CONNECTED,
        ClientDisconnectedEvent::class => self::CLIENT_DISCONNECTED,
        ClientErrorEvent::class => self::CLIENT_ERROR,
        ConnectionRejectedEvent::class => self::CONNECTION_REJECTED,
    ];
}
