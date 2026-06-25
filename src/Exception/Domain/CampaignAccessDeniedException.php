<?php

declare(strict_types=1);

namespace App\Exception\Domain;

use App\Entity\Campaign;
use App\Entity\User;

/**
 * Raised when a user attempts to access a campaign outside their allowed scope.
 */
final class CampaignAccessDeniedException extends BusinessRuleException
{
    public static function forUser(Campaign $campaign, User $user): self
    {
        return new self(sprintf(
            'User "%s" is not allowed to access campaign #%s.',
            $user->getUserIdentifier(),
            $campaign->getId() ?? 'new'
        ));
    }
}