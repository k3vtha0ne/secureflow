<?php

namespace App\Entity;

use App\Repository\CampaignRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Doctrine\Orm\Filter\ExactFilter;
use ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\SortFilter;
use ApiPlatform\Metadata\QueryParameter;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CampaignRepository::class)]
#[ORM\Table(name: 'campaign')]
#[ORM\Index(name: 'idx_campaign_organization_created', columns: ['organization_id', 'created_at'])]
#[ORM\Index(name: 'idx_campaign_created_by_created', columns: ['created_by_id', 'created_at'])]
#[ORM\Index(name: 'idx_campaign_status_scheduled', columns: ['status', 'scheduled_at'])]
/**
 * API read model for document distribution campaigns.
 *
 * Write operations are intentionally not exposed yet: campaign creation must
 * validate ownership, organization scope and document access server-side.
 *
 * Access control will be enforced once API authentication is configured.
 */
#[ApiResource(
    operations: [
        new Get(
            security: "is_granted('CAMPAIGN_VIEW', object)" // VOTER
        ),
        new GetCollection(
            parameters: [
                // Allows partial text search on non-sensitive campaign fields.
                'search[:property]' => new QueryParameter(
                    properties: ['name', 'description'],
                    filter: new PartialSearchFilter()
                ),

                // Allows exact filtering by public campaign lifecycle status.
                'status' => new QueryParameter(
                    property: 'status',
                    filter: new ExactFilter()
                ),

                // Allows explicit sorting without exposing arbitrary internal fields.
                'sortCreatedAt' => new QueryParameter(
                    property: 'createdAt',
                    filter: new SortFilter()
                ),
                'sortScheduledAt' => new QueryParameter(
                    property: 'scheduledAt',
                    filter: new SortFilter()
                ),
                'sortName' => new QueryParameter(
                    property: 'name',
                    filter: new SortFilter()
                ),
                'sortStatus' => new QueryParameter(
                    property: 'status',
                    filter: new SortFilter()
                ),
            ]
        ),
    ],
    normalizationContext: ['groups' => ['campaign:read']],
    paginationItemsPerPage: 20,
    order: ['createdAt' => 'DESC']
)]
class Campaign
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    #[Groups(['campaign:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['campaign:read'])]
    #[Assert\NotBlank(message: 'Campaign name is required.')]
    #[Assert\Length(max: 180)]
    #[ORM\Column(length: 180)]
    private ?string $name = null;

    #[Groups(['campaign:read'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[Groups(['campaign:read'])]
    #[Assert\Choice(
        choices: [
            self::STATUS_DRAFT,
            self::STATUS_SCHEDULED,
            self::STATUS_RUNNING,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ],
        message: 'Invalid campaign status.'
    )]
    #[ORM\Column(length: 30)]
    private ?string $status = null;

    #[Groups(['campaign:read'])]
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $scheduledAt = null;

    #[ORM\ManyToOne(inversedBy: 'campaigns')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organization $organization = null;

    #[ORM\ManyToOne(inversedBy: 'createdCampaigns')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    /**
     * @var Collection<int, Document>
     */
    #[ORM\ManyToMany(targetEntity: Document::class, inversedBy: 'campaigns')]
    private Collection $documents;

    #[Groups(['campaign:read'])]
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[Groups(['campaign:read'])]
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->status = self::STATUS_DRAFT;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?\DateTimeImmutable $scheduledAt): static
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): static
    {
        $this->organization = $organization;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Document $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
        }

        return $this;
    }

    public function removeDocument(Document $document): static
    {
        $this->documents->removeElement($document);

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
