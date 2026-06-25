<?php

declare(strict_types=1);

namespace App\Application\Campaign\Command;

use App\Entity\Document;
use App\Entity\User;

final readonly class CreateCampaignCommand
{
    /**
     * @param list<Document> $documents
     */
    public function __construct(
        private User $createdBy,
        private string $name,
        private ?string $description = null,
        private ?\DateTimeImmutable $scheduledAt = null,
        private array $documents = [],
    ) {
        foreach ($this->documents as $document) {
            if (!$document instanceof Document) {
                throw new \InvalidArgumentException('Campaign documents must be Document instances.');
            }
        }
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    /**
     * @return list<Document>
     */
    public function getDocuments(): array
    {
        return $this->documents;
    }
}
