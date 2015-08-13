<?php

namespace Gos\Bundle\WebSocketBundle\Pipeline;

use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Gos\Component\WebSocketClient\Wamp\Client;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampServerInterface;

class WampPipeline implements WampPipelineInterface
{
    /**
     * @var WampServerInterface
     */
    protected $wampApplication;

    /**
     * @var \SplStack
     */
    protected $pipeStack;

    /**
     * @var WampRouter
     */
    protected $wampRouter;

    /**
     * @param WampRouter          $wampRouter
     */
    public function __construct(WampRouter $wampRouter)
    {
        $this->pipeStack = new \SplStack();
        $this->wampRouter = $wampRouter;
    }

    /**
     * @param WampServerInterface $wampApplication
     */
    public function setWampApplication(WampServerInterface $wampApplication)
    {
        $this->wampApplication = $wampApplication;
    }

    /**
     * @return PipeInterface
     */
    public function pipe()
    {
        $pipe = new Pipe();
        $this->pipeStack->push($pipe);

        return $pipe;
    }

    public function flush()
    {
        /** @var PipeInterface $pipe */
        foreach($this->pipeStack as $pipe){

            list($routeName, $routeAttributes) = $pipe->getRequestData();
            $topic = new Topic($this->wampRouter->generate($routeName, $routeAttributes));
            if(
                $pipe->getForward() === WampPipelineInterface::SUBSCRIPTION ||
                $pipe->getForward() === WampPipelineInterface::UNSUBSCRIPTION
            ) {
                $this->wampApplication->{$pipe->getForward()}(
                    $pipe->getConnection(),
                    $topic
                );
            } else { //It's publication

                $ws = new Client('notification.dev', 1337);
                $ws->connect();
                $ws->publish($topic->getId(), $pipe->getData());
                $ws->disconnect();
//                $this->wampApplication->onPublish(
//                    $pipe->getConnection(),
//                    $topic,
//                    $pipe->getData(),
//                    $pipe->getExclude(),
//                    $pipe->getEligible()
//                );
            }
        }
    }
}