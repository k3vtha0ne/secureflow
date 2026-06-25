<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Campaign\Command;

use App\Application\Campaign\Command\CreateCampaignCommand;
use App\Application\Campaign\Command\CreateCampaignHandler;
use App\Entity\Campaign;
use App\Entity\Document;
use App\Entity\Organization;
use App\Entity\User;
use App\Exception\Domain\DocumentAccessDeniedException;
use App\Service\CampaignSchedulingService;
use App\Service\DocumentAccessService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class CreateCampaignHandlerTest extends TestCase
{
    public function testItCreatesDraftCampaign(): void
    {
        $organization = new Organization();

        $user = new User();
        $user->setOrganization($organization);

        $document = new Document();
        $document->setOrganization($organization);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Campaign::class));

        $entityManager
            ->expects($this->once())
            ->method('flush');

        $handler = new CreateCampaignHandler(
            $entityManager,
            new DocumentAccessService(),
            new CampaignSchedulingService(),
        );

        $campaign = $handler(new CreateCampaignCommand(
            createdBy: $user,
            name: 'Product launch',
            description: 'Launch campaign',
            documents: [$document],
        ));

        self::assertSame('Product launch', $campaign->getName());
        self::assertSame('Launch campaign', $campaign->getDescription());
        self::assertSame(Campaign::STATUS_DRAFT, $campaign->getStatus());
        self::assertSame($organization, $campaign->getOrganization());
        self::assertSame($user, $campaign->getCreatedBy());
        self::assertTrue($campaign->getDocuments()->contains($document));
    }

    public function testItCreatesScheduledCampaignWhenScheduledAtIsProvided(): void
    {
        $organization = new Organization();

        $user = new User();
        $user->setOrganization($organization);

        $scheduledAt = new \DateTimeImmutable('+1 day');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Campaign::class));

        $entityManager
            ->expects($this->once())
            ->method('flush');

        $handler = new CreateCampaignHandler(
            $entityManager,
            new DocumentAccessService(),
            new CampaignSchedulingService(),
        );

        $campaign = $handler(new CreateCampaignCommand(
            createdBy: $user,
            name: 'Scheduled campaign',
            scheduledAt: $scheduledAt,
        ));

        self::assertSame(Campaign::STATUS_SCHEDULED, $campaign->getStatus());
        self::assertSame($scheduledAt, $campaign->getScheduledAt());
    }

    public function testItRejectsDocumentFromAnotherOrganization(): void
    {
        $userOrganization = new Organization();
        $otherOrganization = new Organization();

        $user = new User();
        $user->setOrganization($userOrganization);

        $document = new Document();
        $document->setOrganization($otherOrganization);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('persist');

        $entityManager
            ->expects($this->never())
            ->method('flush');

        $handler = new CreateCampaignHandler(
            $entityManager,
            new DocumentAccessService(),
            new CampaignSchedulingService(),
        );

        $this->expectException(DocumentAccessDeniedException::class);

        $handler(new CreateCampaignCommand(
            createdBy: $user,
            name: 'Invalid campaign',
            documents: [$document],
        ));
    }

    public function testItRejectsCreatorWithoutOrganization(): void
    {
        $user = new User();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects($this->never())
            ->method('persist');

        $entityManager
            ->expects($this->never())
            ->method('flush');

        $handler = new CreateCampaignHandler(
            $entityManager,
            new DocumentAccessService(),
            new CampaignSchedulingService(),
        );

        $this->expectException(\InvalidArgumentException::class);

        $handler(new CreateCampaignCommand(
            createdBy: $user,
            name: 'Invalid campaign',
        ));
    }
}
