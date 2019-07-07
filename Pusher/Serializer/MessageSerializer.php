<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher\Serializer;

use Gos\Bundle\WebSocketBundle\Pusher\Message;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;

final class MessageSerializer
{
    /**
     * @var Serializer
     */
    private $serializer;

    public function __construct()
    {
        $normalizers = [
            new GetSetMethodNormalizer(),
        ];

        $encoders = [
            new JsonEncoder(),
        ];

        $this->serializer = new Serializer($normalizers, $encoders);
    }

    /**
     * @return string
     */
    public function serialize(Message $message)
    {
        return $this->serializer->serialize($message, 'json');
    }

    public function deserialize($data)
    {
        return $this->serializer->deserialize($data, Message::class, 'json');
    }
}
