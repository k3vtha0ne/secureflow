<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Campaign;
use App\Entity\User;

/**
 * Centralizes business access rules for Campaign entities.
 *
 * Security voters should delegate to this service instead of carrying
 * domain rules directly. This keeps access logic reusable and easier to test.
 */
final class CampaignAccessService
{
    public function canView(Campaign $campaign, User $user): bool
    {
        if (null === $user->getOrganization()) {
            return false;
        }

        return $campaign->getOrganization()?->getId() === $user->getOrganization()->getId();
    }
}