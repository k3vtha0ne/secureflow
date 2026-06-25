<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Campaign;
use App\Exception\Domain\CampaignCannotRunException;

/**
 * Centralizes campaign scheduling and execution rules.
 *
 * This service answers domain questions such as:
 * - can this campaign be scheduled?
 * - can this campaign run now?
 * - should execution be blocked by a business rule?
 */
final class CampaignSchedulingService
{
    public function canBeScheduled(Campaign $campaign): bool
    {
        return Campaign::STATUS_DRAFT === $campaign->getStatus()
            && null !== $campaign->getScheduledAt();
    }

    public function canRun(Campaign $campaign, \DateTimeImmutable $now): bool
    {
        $scheduledAt = $campaign->getScheduledAt();

        if (Campaign::STATUS_SCHEDULED !== $campaign->getStatus()) {
            return false;
        }

        if (null === $scheduledAt) {
            return false;
        }

        return $scheduledAt <= $now;
    }

    public function denyUnlessCanRun(Campaign $campaign, \DateTimeImmutable $now): void
    {
        if (!$this->canRun($campaign, $now)) {
            throw CampaignCannotRunException::forCampaign($campaign);
        }
    }
}