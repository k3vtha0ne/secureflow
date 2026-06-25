<?php

declare(strict_types=1);

namespace App\Application\Campaign\Command;

use App\Entity\Campaign;
use App\Service\CampaignSchedulingService;
use App\Service\DocumentAccessService;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CreateCampaignHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DocumentAccessService $documentAccessService,
        private CampaignSchedulingService $campaignSchedulingService,
    ) {
    }

    public function __invoke(CreateCampaignCommand $command): Campaign
    {
        $createdBy = $command->getCreatedBy();
        $organization = $createdBy->getOrganization();

        if (null === $organization) {
            throw new \InvalidArgumentException('Campaign creator must belong to an organization.');
        }

        $campaign = new Campaign();
        $campaign
            ->setName(trim($command->getName()))
            ->setDescription($command->getDescription())
            ->setCreatedBy($createdBy)
            ->setOrganization($organization)
            ->setScheduledAt($command->getScheduledAt());

        foreach ($command->getDocuments() as $document) {
            $this->documentAccessService->denyUnlessCanView($document, $createdBy);
            $campaign->addDocument($document);
        }

        if ($this->campaignSchedulingService->canBeScheduled($campaign)) {
            $campaign->setStatus(Campaign::STATUS_SCHEDULED);
        }

        $this->entityManager->persist($campaign);
        $this->entityManager->flush();

        return $campaign;
    }
}
