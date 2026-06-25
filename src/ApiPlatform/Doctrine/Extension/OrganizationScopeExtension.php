<?php

declare(strict_types=1);

namespace App\ApiPlatform\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Campaign;
use App\Entity\Document;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Restricts tenant-owned API resources to the current user's organization.
 *
 * This extension applies at Doctrine query level:
 * - collections only return resources owned by the user's organization;
 * - item reads return 404 when the resource belongs to another organization.
 *
 * In this project, ROLE_ADMIN means organization admin, not platform-wide admin.
 * Therefore, admins must also stay scoped to their own organization.
 */
final readonly class OrganizationScopeExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    private const SUPPORTED_RESOURCES = [
        Document::class,
        Campaign::class,
    ];

    public function __construct(
        private Security $security,
    ) {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        $this->applyOrganizationScope($queryBuilder, $resourceClass);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        $this->applyOrganizationScope($queryBuilder, $resourceClass);
    }

    private function applyOrganizationScope(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if (!in_array($resourceClass, self::SUPPORTED_RESOURCES, true)) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof User || null === $user->getOrganization()) {
            // Anonymous users are normally blocked by access_control.
            // This fallback prevents accidental data exposure if configuration changes later.
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0];

        $queryBuilder
            ->andWhere(sprintf('%s.organization = :currentOrganization', $rootAlias))
            ->setParameter('currentOrganization', $user->getOrganization());
    }
}