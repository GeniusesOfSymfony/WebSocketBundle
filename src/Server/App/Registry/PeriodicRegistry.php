<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Server\App\Registry;

use Gos\Bundle\WebSocketBundle\Periodic\PeriodicInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
final class PeriodicRegistry
{
    /**
     * @var PeriodicInterface[]
     */
    private array $periodics = [];

    public function addPeriodic(PeriodicInterface $periodic): void
    {
        $this->periodics[] = $periodic;
    }

    /**
     * @return PeriodicInterface[]
     */
    public function getPeriodics(): array
    {
        return $this->periodics;
    }

    public function hasPeriodic(PeriodicInterface $periodic): bool
    {
        return \in_array($periodic, $this->periodics);
    }
}
