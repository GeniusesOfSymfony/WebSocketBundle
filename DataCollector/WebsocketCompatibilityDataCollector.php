<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;
use Symfony\Component\HttpKernel\Kernel;

if (Kernel::MAJOR_VERSION >= 5) {
    /**
     * Compatibility file loader for Symfony 5.0 and later.
     *
     * @internal To be removed when dropping support for Symfony 4.4 and earlier
     */
    abstract class WebsocketCompatibilityDataCollector extends DataCollector implements LateDataCollectorInterface
    {
        public function collect(Request $request, Response $response, \Throwable $exception = null): void
        {
        }
    }
} else {
    /**
     * Compatibility data collector for Symfony 4.4 and earlier.
     *
     * @internal To be removed when dropping support for Symfony 4.4 and earlier
     */
    abstract class WebsocketCompatibilityDataCollector extends DataCollector implements LateDataCollectorInterface
    {
        public function collect(Request $request, Response $response, \Exception $exception = null): void
        {
        }
    }
}
