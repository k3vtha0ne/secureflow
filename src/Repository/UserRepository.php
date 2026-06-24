<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Retourne les utilisateurs d'une organisation, avec pagination.
     */
    public function findByOrganization(
        Organization $organization,
        int $limit = 20,
        int $offset = 0
    ): array {
        return $this->createQueryBuilder('u')
            ->andWhere('u.organization = :organization')
            ->setParameter('organization', $organization)
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les utilisateurs d'une organisation.
     */
    public function countByOrganization(Organization $organization): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.organization = :organization')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Recherche des utilisateurs d'une organisation par nom, prénom ou email.
     */
    public function searchByOrganization(
        Organization $organization,
        string $search,
        int $limit = 20
    ): array {
        return $this->createQueryBuilder('u')
            ->andWhere('u.organization = :organization')
            ->andWhere('LOWER(u.email) LIKE :search OR LOWER(u.firstName) LIKE :search OR LOWER(u.lastName) LIKE :search')
            ->setParameter('organization', $organization)
            ->setParameter('search', '%' . mb_strtolower($search) . '%')
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
