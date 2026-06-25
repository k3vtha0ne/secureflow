<?php

declare(strict_types=1);

namespace App\Application\Campaign\Query;

use App\Entity\Organization;

final readonly class CampaignStatsQuery
{
    public function __construct(
        private Organization $organization,
    ) {
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }
}
