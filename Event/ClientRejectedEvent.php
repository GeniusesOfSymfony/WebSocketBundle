<?php

namespace Gos\Bundle\WebSocketBundle\Event;

use Guzzle\Http\Message\RequestInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class ClientRejectedEvent extends Event
{
    /**
     * @var string
     */
    protected $origin;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param string           $origin
     * @param RequestInterface $request
     */
    public function __construct($origin, RequestInterface $request = null)
    {
        $this->origin = $origin;
        $this->request = $request;
    }

    /**
     * @return string
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return bool
     */
    public function hasRequest()
    {
        return null !== $this->request;
    }
}
