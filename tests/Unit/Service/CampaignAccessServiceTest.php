<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Campaign;
use App\Entity\Organization;
use App\Entity\User;
use App\Service\CampaignAccessService;
use PHPUnit\Framework\TestCase;

final class CampaignAccessServiceTest extends TestCase
{
    private CampaignAccessService $service;

    protected function setUp(): void
    {
        $this->service = new CampaignAccessService();
    }

    public function testUserCanViewCampaignFromSameOrganization(): void
    {
        $organization = new Organization();
        $organization->setName('Alpha');

        $user = new User();
        $user->setOrganization($organization);

        $campaign = new Campaign();
        $campaign->setOrganization($organization);

        self::assertTrue($this->service->canView($campaign, $user));
    }

    public function testUserCannotViewCampaignFromAnotherOrganization(): void
    {
        $userOrganization = new Organization();
        $userOrganization->setName('Alpha');

        $campaignOrganization = new Organization();
        $campaignOrganization->setName('Beta');

        $user = new User();
        $user->setOrganization($userOrganization);

        $campaign = new Campaign();
        $campaign->setOrganization($campaignOrganization);

        self::assertFalse($this->service->canView($campaign, $user));
    }

    public function testUserWithoutOrganizationCannotViewCampaign(): void
    {
        $campaignOrganization = new Organization();
        $campaignOrganization->setName('Alpha');

        $user = new User();

        $campaign = new Campaign();
        $campaign->setOrganization($campaignOrganization);

        self::assertFalse($this->service->canView($campaign, $user));
    }

    public function testCampaignWithoutOrganizationCannotBeViewed(): void
    {
        $userOrganization = new Organization();
        $userOrganization->setName('Alpha');

        $user = new User();
        $user->setOrganization($userOrganization);

        $campaign = new Campaign();

        self::assertFalse($this->service->canView($campaign, $user));
    }
}