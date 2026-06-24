<?php

namespace App\Repository;

use App\Entity\Campaign;
use App\Entity\Organization;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Campaign>
 */
class CampaignRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Campaign::class);
    }

    /**
     * Retourne les campagnes d'une organisation, avec pagination.
     */
    public function findByOrganization(
        Organization $organization,
        int $limit = 20,
        int $offset = 0
    ): array {
        return $this->createQueryBuilder('c')
            ->andWhere('c.organization = :organization')
            ->setParameter('organization', $organization)
            ->orderBy('c.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les campagnes créées par un utilisateur.
     */
    public function findCreatedByUser(
        User $user,
        int $limit = 20,
        int $offset = 0
    ): array {
        return $this->createQueryBuilder('c')
            ->andWhere('c.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les campagnes planifiées qui doivent être exécutées.
     */
    public function findScheduledToRun(\DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.status = :status')
            ->andWhere('c.scheduledAt IS NOT NULL')
            ->andWhere('c.scheduledAt <= :now')
            ->setParameter('status', Campaign::STATUS_SCHEDULED)
            ->setParameter('now', $now)
            ->orderBy('c.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les campagnes par statut pour une organisation.
     *
     * Exemple :
     * [
     *     ['status' => 'draft', 'total' => 4],
     *     ['status' => 'scheduled', 'total' => 2],
     * ]
     */
    public function countByStatusForOrganization(Organization $organization): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.status AS status')
            ->addSelect('COUNT(c.id) AS total')
            ->andWhere('c.organization = :organization')
            ->setParameter('organization', $organization)
            ->groupBy('c.status')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }
}
