<?php

declare(strict_types=1);

namespace App\Exception\Domain;

use App\Entity\Campaign;

/**
 * Raised when a campaign cannot be executed because its lifecycle state
 * or scheduling data does not allow it.
 */
final class CampaignCannotRunException extends BusinessRuleException
{
    public static function forCampaign(Campaign $campaign): self
    {
        return new self(sprintf(
            'Campaign #%s cannot be run from status "%s" with scheduled date "%s".',
            $campaign->getId() ?? 'new',
            $campaign->getStatus() ?? 'unknown',
            $campaign->getScheduledAt()?->format(\DateTimeInterface::ATOM) ?? 'none'
        ));
    }
}