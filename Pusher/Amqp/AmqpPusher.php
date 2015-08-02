<?php

namespace Gos\Bundle\WebSocketBundle\Pusher\Amqp;

use Gos\Bundle\WebSocketBundle\Pusher\AbstractPusher;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AmqpPusher extends AbstractPusher
{
    /**
     * @param string $data
     */
    protected function doPush($data)
    {

    }

    public function close()
    {

    }
}
