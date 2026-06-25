<?php

declare(strict_types=1);

namespace App\Application\Campaign\Query;

use App\Entity\Campaign;

final readonly class CampaignStats
{
    /**
     * @param array<string, int> $byStatus
     */
    public function __construct(
        private int $total,
        private array $byStatus,
    ) {
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @return array<string, int>
     */
    public function getByStatus(): array
    {
        return $this->byStatus;
    }

    public function getDraft(): int
    {
        return $this->byStatus[Campaign::STATUS_DRAFT] ?? 0;
    }

    public function getScheduled(): int
    {
        return $this->byStatus[Campaign::STATUS_SCHEDULED] ?? 0;
    }

    public function getRunning(): int
    {
        return $this->byStatus[Campaign::STATUS_RUNNING] ?? 0;
    }

    public function getCompleted(): int
    {
        return $this->byStatus[Campaign::STATUS_COMPLETED] ?? 0;
    }

    public function getCancelled(): int
    {
        return $this->byStatus[Campaign::STATUS_CANCELLED] ?? 0;
    }
}
