#Events

Sometimes you will need to perform a server side action when a user connects or disconnects. Gos WebSocket will fire events for many reasons:

* Server starting
* Client Connects
* Client Rejected
* Client Disconnects
* On Socket Error

By using Symfony2 Event Listeners, you can be notified when any of these events occur.

###Step 1: Create Event Listener Class

Create a [Symfony 2 event listener class](http://symfony.com/doc/current/cookbook/service_container/event_listener.html)

```php
<?php
namespace Acme\HelloBundle\EventListener;

use Gos\Bundle\WebSocketBundle\Event\ClientEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent;
use Gos\Bundle\WebSocketBundle\Event\ServerEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientRejectedEvent;

class AcmeClientEventListener
{
    /**
     * Called whenever a client connects
     *
     * @param ClientEvent $event
     */
    public function onClientConnect(ClientEvent $event)
    {
        $conn = $event->getConnection();

        echo $conn->resourceId . " connected" . PHP_EOL;
    }

    /**
     * Called whenever a client disconnects
     *
     * @param ClientEvent $event
     */
    public function onClientDisconnect(ClientEvent $event)
    {
        $conn = $event->getConnection();

        echo $conn->resourceId . " disconnected" . PHP_EOL;
    }

    /**
     * Called whenever a client errors
     *
     * @param ClientErrorEvent $event
     */
    public function onClientError(ClientErrorEvent $event)
    {
        $conn = $event->getConnection();
        $e = $event->getException();

        echo "connection error occurred: " . $e->getMessage() . PHP_EOL;
    }

    /**
     * Called whenever server start
     *
     * @param ServentEvent $event
     */
    public function onServerStart(ServerEvent $event)
    {
    	$event = $event->getEventLoop();

        echo 'Server was successfully started !'. PHP_EOL;
    }

    /**
     * Called whenever client is rejected by application
     *
     * @param ClientRejectedEvent $event
     */
	public function onClientRejected(ClientRejectedEvent $event)
    {
    	$origin = $event->getOrigin;

		echo 'connection rejected from '. $origin . PHP_EOL;
    }
}
```

###Step 2: Register it as a service

Add this to your bundles "services.yml"

####Available events:
* **gos_web_socket.server_launched**
* **gos_web_socket.client_connected**
* **gos_web_socket.client_disconnected**
* **gos_web_socket.client_rejected**
* **gos_web_socket.client_error**

Default event listener :
```yml
gos_web_socket_server.client_event.listener:
    class: Gos\Bundle\WebSocketBundle\Event\ClientEventListener
    tags:
        - { name: kernel.event_listener, event: 'gos_web_socket.client_connected', method: onClientConnect }
        - { name: kernel.event_listener, event: 'gos_web_socket.client_disconnected', method: onClientDisconnect }
        - { name: kernel.event_listener, event: 'gos_web_socket.client_error', method: onClientError }
        - { name: kernel.event_listener, event: 'gos_web_socket.server_launched', method: onServerStart }
        - { name: kernel.event_listener, event: 'gos_web_socket.client_rejected', method: onClientRejected }
```

You can add your own.

You will now notice that when a user connects or disconnects from your server, you will be given a notification in the command line.