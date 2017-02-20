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
    protected $msg;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param string           $msg
     * @param RequestInterface $request
     */
    public function __construct($msg, RequestInterface $request = null)
    {
        $this->msg = $msg;
        $this->request = $request;
    }

    /**
     * @return string
     */
    public function getMsg()
    {
        return $this->msg;
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
