<?php

namespace App\Entity;

use App\Controller\Api\ArchiveDocumentController;
use App\Controller\Api\PublishDocumentController;
use App\Controller\Api\ViewDocumentController;
use App\Repository\DocumentRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
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

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\Table(name: 'document')]
#[ORM\Index(name: 'idx_document_organization_deleted_created', columns: ['organization_id', 'is_deleted', 'created_at'])]
#[ORM\Index(name: 'idx_document_owner_deleted_created', columns: ['owner_id', 'is_deleted', 'created_at'])]
#[ORM\Index(name: 'idx_document_status', columns: ['status'])]
/**
 * API read model for secured documents.
 *
 * Generic write operations are intentionally not exposed yet: document creation
 * must assign the owner and organization server-side to avoid trusting client input.
 *
 * Lifecycle write operations are exposed as dedicated business actions.
 *
 * Access control is enforced through JWT authentication, organization scoping and voters.
 */
#[ApiResource(
    operations: [
        new Get(
            controller: ViewDocumentController::class,
            security: "is_granted('DOCUMENT_VIEW', object)",
            read: true,
            name: 'document_view'
        ),
        new Post(
            uriTemplate: '/documents/{id}/publish',
            controller: PublishDocumentController::class,
            security: "is_granted('DOCUMENT_VIEW', object)",
            read: true,
            deserialize: false,
            validate: false,
            status: 200,
            name: 'document_publish'
        ),
        new Post(
            uriTemplate: '/documents/{id}/archive',
            controller: ArchiveDocumentController::class,
            security: "is_granted('DOCUMENT_VIEW', object)",
            read: true,
            deserialize: false,
            validate: false,
            status: 200,
            name: 'document_archive'
        ),
        new GetCollection(
            parameters: [
                // Allows partial text search on non-sensitive document fields.
                'search[:property]' => new QueryParameter(
                    properties: ['title', 'description'],
                    filter: new PartialSearchFilter()
                ),

                // Allows exact filtering by public document lifecycle status.
                'status' => new QueryParameter(
                    property: 'status',
                    filter: new ExactFilter()
                ),

                // Allows explicit sorting without exposing arbitrary internal fields.
                'sortCreatedAt' => new QueryParameter(
                    property: 'createdAt',
                    filter: new SortFilter()
                ),
                'sortTitle' => new QueryParameter(
                    property: 'title',
                    filter: new SortFilter()
                ),
                'sortStatus' => new QueryParameter(
                    property: 'status',
                    filter: new SortFilter()
                ),
            ]
        ),
    ],
    normalizationContext: ['groups' => ['document:read']],
    paginationItemsPerPage: 20,
    order: ['createdAt' => 'DESC']
)]
class Document
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    #[Groups(['document:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['document:read'])]
    #[Assert\NotBlank(message: 'Document title is required.')]
    #[Assert\Length(max: 180)]
    #[ORM\Column(length: 180)]
    private ?string $title = null;

    #[Groups(['document:read'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[Groups(['document:read'])]
    #[Assert\Choice(
        choices: [
            self::STATUS_DRAFT,
            self::STATUS_PUBLISHED,
            self::STATUS_ARCHIVED,
        ],
        message: 'Invalid document status.'
    )]
    #[ORM\Column(length: 30)]
    private ?string $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $storagePath = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organization $organization = null;

    #[ORM\Column]
    private ?bool $isDeleted = null;

    #[Groups(['document:read'])]
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[Groups(['document:read'])]
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Campaign>
     */
    #[ORM\ManyToMany(targetEntity: Campaign::class, mappedBy: 'documents')]
    private Collection $campaigns;

    public function __construct()
    {
        $this->status = self::STATUS_DRAFT;
        $this->isDeleted = false;
        $this->createdAt = new \DateTimeImmutable();
        $this->campaigns = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

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

    public function getStoragePath(): ?string
    {
        return $this->storagePath;
    }

    public function setStoragePath(?string $storagePath): static
    {
        $this->storagePath = $storagePath;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

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

    public function isDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): static
    {
        $this->isDeleted = $isDeleted;

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

    /**
     * @return Collection<int, Campaign>
     */
    public function getCampaigns(): Collection
    {
        return $this->campaigns;
    }

    public function addCampaign(Campaign $campaign): static
    {
        if (!$this->campaigns->contains($campaign)) {
            $this->campaigns->add($campaign);
            $campaign->addDocument($this);
        }

        return $this;
    }

    public function removeCampaign(Campaign $campaign): static
    {
        if ($this->campaigns->removeElement($campaign)) {
            $campaign->removeDocument($this);
        }

        return $this;
    }
}
