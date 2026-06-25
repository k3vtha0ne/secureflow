<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Campaign;
use App\Exception\Domain\CampaignCannotRunException;
use App\Service\CampaignSchedulingService;
use PHPUnit\Framework\TestCase;

final class CampaignSchedulingServiceTest extends TestCase
{
    private CampaignSchedulingService $service;

    protected function setUp(): void
    {
        $this->service = new CampaignSchedulingService();
    }

    public function testDraftCampaignWithScheduledDateCanBeScheduled(): void
    {
        $campaign = new Campaign();
        $campaign->setStatus(Campaign::STATUS_DRAFT);
        $campaign->setScheduledAt(new \DateTimeImmutable('+1 day'));

        self::assertTrue($this->service->canBeScheduled($campaign));
    }

    public function testDraftCampaignWithoutScheduledDateCannotBeScheduled(): void
    {
        $campaign = new Campaign();
        $campaign->setStatus(Campaign::STATUS_DRAFT);
        $campaign->setScheduledAt(null);

        self::assertFalse($this->service->canBeScheduled($campaign));
    }

    public function testScheduledCampaignWithPastScheduledDateCanRun(): void
    {
        $now = new \DateTimeImmutable('2026-01-01 12:00:00');

        $campaign = new Campaign();
        $campaign->setStatus(Campaign::STATUS_SCHEDULED);
        $campaign->setScheduledAt(new \DateTimeImmutable('2026-01-01 11:00:00'));

        self::assertTrue($this->service->canRun($campaign, $now));
    }

    public function testScheduledCampaignWithFutureScheduledDateCannotRun(): void
    {
        $now = new \DateTimeImmutable('2026-01-01 12:00:00');

        $campaign = new Campaign();
        $campaign->setStatus(Campaign::STATUS_SCHEDULED);
        $campaign->setScheduledAt(new \DateTimeImmutable('2026-01-01 13:00:00'));

        self::assertFalse($this->service->canRun($campaign, $now));
    }

    public function testDraftCampaignCannotRun(): void
    {
        $campaign = new Campaign();
        $campaign->setStatus(Campaign::STATUS_DRAFT);
        $campaign->setScheduledAt(new \DateTimeImmutable('-1 day'));

        self::assertFalse($this->service->canRun($campaign, new \DateTimeImmutable()));
    }

    public function testScheduledCampaignWithoutScheduledDateCannotRun(): void
    {
        $campaign = new Campaign();
        $campaign->setStatus(Campaign::STATUS_SCHEDULED);
        $campaign->setScheduledAt(null);

        self::assertFalse($this->service->canRun($campaign, new \DateTimeImmutable()));
    }

    public function testDenyUnlessCanRunDoesNotThrowWhenCampaignCanRun(): void
    {
        $now = new \DateTimeImmutable('2026-01-01 12:00:00');

        $campaign = new Campaign();
        $campaign->setStatus(Campaign::STATUS_SCHEDULED);
        $campaign->setScheduledAt(new \DateTimeImmutable('2026-01-01 11:00:00'));

        $this->service->denyUnlessCanRun($campaign, $now);

        self::assertTrue(true);
    }

    public function testDenyUnlessCanRunThrowsWhenCampaignCannotRun(): void
    {
        $now = new \DateTimeImmutable('2026-01-01 12:00:00');

        $campaign = new Campaign();
        $campaign->setStatus(Campaign::STATUS_SCHEDULED);
        $campaign->setScheduledAt(new \DateTimeImmutable('2026-01-01 13:00:00'));

        $this->expectException(CampaignCannotRunException::class);

        $this->service->denyUnlessCanRun($campaign, $now);
    }
}