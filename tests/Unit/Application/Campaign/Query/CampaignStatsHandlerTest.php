<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Campaign\Query;

use App\Application\Campaign\Query\CampaignStatsHandler;
use App\Application\Campaign\Query\CampaignStatsQuery;
use App\Entity\Campaign;
use App\Entity\Organization;
use App\Repository\CampaignRepository;
use PHPUnit\Framework\TestCase;

final class CampaignStatsHandlerTest extends TestCase
{
    public function testItReturnsCampaignStatsForOrganization(): void
    {
        $organization = new Organization();

        $repository = $this->createMock(CampaignRepository::class);
        $repository
            ->expects($this->once())
            ->method('countByStatusForOrganization')
            ->with($organization)
            ->willReturn([
                ['status' => Campaign::STATUS_DRAFT, 'total' => '2'],
                ['status' => Campaign::STATUS_SCHEDULED, 'total' => '3'],
                ['status' => Campaign::STATUS_COMPLETED, 'total' => '1'],
            ]);

        $handler = new CampaignStatsHandler($repository);

        $stats = $handler(new CampaignStatsQuery($organization));

        self::assertSame(6, $stats->getTotal());
        self::assertSame(2, $stats->getDraft());
        self::assertSame(3, $stats->getScheduled());
        self::assertSame(0, $stats->getRunning());
        self::assertSame(1, $stats->getCompleted());
        self::assertSame(0, $stats->getCancelled());
        self::assertSame([
            Campaign::STATUS_DRAFT => 2,
            Campaign::STATUS_SCHEDULED => 3,
            Campaign::STATUS_COMPLETED => 1,
        ], $stats->getByStatus());
    }
}
