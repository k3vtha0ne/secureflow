<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Campaign;
use App\Entity\User;
use App\Exception\Domain\CampaignAccessDeniedException;

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
        $userOrganization = $user->getOrganization();
        $campaignOrganization = $campaign->getOrganization();

        if (null === $userOrganization || null === $campaignOrganization) {
            return false;
        }

        if ($userOrganization === $campaignOrganization) {
            return true;
        }

        if (null === $userOrganization->getId() || null === $campaignOrganization->getId()) {
            return false;
        }

        return $campaignOrganization->getId() === $userOrganization->getId();
    }

    public function denyUnlessCanView(Campaign $campaign, User $user): void
    {
        if (!$this->canView($campaign, $user)) {
            throw CampaignAccessDeniedException::forUser($campaign, $user);
        }
    }
}