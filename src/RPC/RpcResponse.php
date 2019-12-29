<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\RPC;

use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\RpcDispatcherInterface;

@trigger_error(sprintf('The %s class is deprecated will be removed in 3.0. Return responses from RPC handlers as arrays or implement a custom %s with support for DTOs.', RpcResponse::class, RpcDispatcherInterface::class), E_USER_DEPRECATED);

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 *
 * @deprecated to be removed in 3.0. Return responses from RPC handlers as arrays or implement a custom dispatcher with support for DTOs.
 */
final class RpcResponse
{
    private array $data = [];

    /**
     * @param mixed $data
     */
    public function __construct($data, string $prefix = 'result')
    {
        $this->data[$prefix] = $data;
    }

    /**
     * @param mixed $data
     */
    public function setData(string $key, $data, string $prefix = 'result'): void
    {
        $this->data[$prefix][$key] = $data;
    }

    /**
     * @param mixed $data
     */
    public function addData($data, string $prefix = 'result'): void
    {
        $this->data[$prefix] = array_combine($this->data[$prefix], $data);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
