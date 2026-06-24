<?php

namespace App\Repository;

use App\Entity\AccessLog;
use App\Entity\Document;
use App\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccessLog>
 */
class AccessLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessLog::class);
    }

    /**
     * Retourne les derniers logs d'accès d'une organisation.
     */
    public function findRecentByOrganization(
        Organization $organization,
        int $limit = 50
    ): array {
        return $this->createQueryBuilder('al')
            ->andWhere('al.organization = :organization')
            ->setParameter('organization', $organization)
            ->orderBy('al.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les derniers logs liés à un document.
     */
    public function findRecentByDocument(
        Document $document,
        int $limit = 50
    ): array {
        return $this->createQueryBuilder('al')
            ->andWhere('al.document = :document')
            ->setParameter('document', $document)
            ->orderBy('al.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les accès par document pour une organisation.
     *
     * Retourne un tableau de lignes :
     * [
     *     ['documentId' => 1, 'documentTitle' => '...', 'accessCount' => 12],
     * ]
     */
    public function countAccessesByDocumentForOrganization(Organization $organization): array
    {
        return $this->createQueryBuilder('al')
            ->select('d.id AS documentId')
            ->addSelect('d.title AS documentTitle')
            ->addSelect('COUNT(al.id) AS accessCount')
            ->join('al.document', 'd')
            ->andWhere('al.organization = :organization')
            ->setParameter('organization', $organization)
            ->groupBy('d.id')
            ->addGroupBy('d.title')
            ->orderBy('accessCount', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Compte les logs par type d'action pour une organisation.
     *
     * Exemple :
     * [
     *     ['action' => 'view', 'total' => 42],
     *     ['action' => 'download', 'total' => 8],
     * ]
     */
    public function countActionsByOrganization(Organization $organization): array
    {
        return $this->createQueryBuilder('al')
            ->select('al.action AS action')
            ->addSelect('COUNT(al.id) AS total')
            ->andWhere('al.organization = :organization')
            ->setParameter('organization', $organization)
            ->groupBy('al.action')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }
}
