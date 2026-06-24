<?php

namespace App\Repository;

use App\Entity\Document;
use App\Entity\Organization;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Document>
 */
class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    /**
     * Retourne les documents non supprimés d'une organisation, du plus récent au plus ancien.
     */
    public function findNotDeletedByOrganization(
        Organization $organization,
        int $limit = 20,
        int $offset = 0
    ): array {
        return $this->createQueryBuilder('d')
            ->andWhere('d.organization = :organization')
            ->andWhere('d.isDeleted = false')
            ->setParameter('organization', $organization)
            ->orderBy('d.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les documents non supprimés d'une organisation.
     */
    public function countNotDeletedByOrganization(Organization $organization): int
    {
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('d.organization = :organization')
            ->andWhere('d.isDeleted = false')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Retourne les documents non supprimés possédés par un utilisateur.
     */
    public function findOwnedByUser(
        User $user,
        int $limit = 20,
        int $offset = 0
    ): array {
        return $this->createQueryBuilder('d')
            ->andWhere('d.owner = :user')
            ->andWhere('d.isDeleted = false')
            ->setParameter('user', $user)
            ->orderBy('d.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
