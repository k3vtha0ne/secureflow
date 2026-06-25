<?php

declare(strict_types=1);

namespace App\Application\Campaign\Query;

use App\Repository\CampaignRepository;

final readonly class CampaignStatsHandler
{
    public function __construct(
        private CampaignRepository $campaignRepository,
    ) {
    }

    public function __invoke(CampaignStatsQuery $query): CampaignStats
    {
        $rows = $this->campaignRepository->countByStatusForOrganization($query->getOrganization());

        $byStatus = [];
        $total = 0;

        foreach ($rows as $row) {
            $status = (string) $row['status'];
            $count = (int) $row['total'];

            $byStatus[$status] = $count;
            $total += $count;
        }

        return new CampaignStats($total, $byStatus);
    }
}
