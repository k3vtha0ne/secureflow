<?php

namespace App\DataFixtures;

use App\Entity\AccessLog;
use App\Entity\Campaign;
use App\Entity\Document;
use App\Entity\Organization;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $alphaOrg = (new Organization())
            ->setName('Alpha Security');

        $betaOrg = (new Organization())
            ->setName('Beta Marketing');

        $manager->persist($alphaOrg);
        $manager->persist($betaOrg);

        $admin = $this->createUser(
            email: 'admin@alpha.test',
            firstName: 'Alice',
            lastName: 'Martin',
            roles: ['ROLE_ADMIN'],
            organization: $alphaOrg,
        );

        $managerUser = $this->createUser(
            email: 'manager@alpha.test',
            firstName: 'Nicolas',
            lastName: 'Durand',
            roles: ['ROLE_MANAGER'],
            organization: $alphaOrg,
        );

        $betaUser = $this->createUser(
            email: 'user@beta.test',
            firstName: 'Emma',
            lastName: 'Bernard',
            roles: ['ROLE_USER'],
            organization: $betaOrg,
        );

        $manager->persist($admin);
        $manager->persist($managerUser);
        $manager->persist($betaUser);

        $securityPolicy = (new Document())
            ->setTitle('Security Policy')
            ->setDescription('Internal security policy document.')
            ->setStatus(Document::STATUS_PUBLISHED)
            ->setStoragePath('/documents/security-policy.pdf')
            ->setOwner($admin)
            ->setOrganization($alphaOrg);

        $accessProcedure = (new Document())
            ->setTitle('Access Procedure')
            ->setDescription('Procedure for secure document access.')
            ->setStatus(Document::STATUS_DRAFT)
            ->setStoragePath('/documents/access-procedure.pdf')
            ->setOwner($managerUser)
            ->setOrganization($alphaOrg);

        $archivedAudit = (new Document())
            ->setTitle('Archived Audit')
            ->setDescription('Old audit report.')
            ->setStatus(Document::STATUS_ARCHIVED)
            ->setStoragePath('/documents/archived-audit.pdf')
            ->setOwner($admin)
            ->setOrganization($alphaOrg);

        $marketingPlan = (new Document())
            ->setTitle('Marketing Plan')
            ->setDescription('Campaign planning document.')
            ->setStatus(Document::STATUS_PUBLISHED)
            ->setStoragePath('/documents/marketing-plan.pdf')
            ->setOwner($betaUser)
            ->setOrganization($betaOrg);

        $manager->persist($securityPolicy);
        $manager->persist($accessProcedure);
        $manager->persist($archivedAudit);
        $manager->persist($marketingPlan);

        $alphaCampaign = (new Campaign())
            ->setName('Alpha onboarding campaign')
            ->setDescription('Initial secure document sharing campaign.')
            ->setStatus(Campaign::STATUS_SCHEDULED)
            ->setScheduledAt(new \DateTimeImmutable('-1 hour'))
            ->setOrganization($alphaOrg)
            ->setCreatedBy($admin)
            ->addDocument($securityPolicy)
            ->addDocument($accessProcedure);

        $betaCampaign = (new Campaign())
            ->setName('Beta launch campaign')
            ->setDescription('Marketing document distribution.')
            ->setStatus(Campaign::STATUS_DRAFT)
            ->setOrganization($betaOrg)
            ->setCreatedBy($betaUser)
            ->addDocument($marketingPlan);

        $manager->persist($alphaCampaign);
        $manager->persist($betaCampaign);

        $logs = [
            $this->createAccessLog($admin, $securityPolicy, $alphaOrg, AccessLog::ACTION_VIEW),
            $this->createAccessLog($admin, $securityPolicy, $alphaOrg, AccessLog::ACTION_DOWNLOAD),
            $this->createAccessLog($managerUser, $securityPolicy, $alphaOrg, AccessLog::ACTION_VIEW),
            $this->createAccessLog($managerUser, $accessProcedure, $alphaOrg, AccessLog::ACTION_CREATE),
            $this->createAccessLog($betaUser, $marketingPlan, $betaOrg, AccessLog::ACTION_VIEW),
            $this->createAccessLog($betaUser, $marketingPlan, $betaOrg, AccessLog::ACTION_DOWNLOAD),
        ];

        foreach ($logs as $log) {
            $manager->persist($log);
        }

        $manager->flush();
    }

    private function createUser(
        string $email,
        string $firstName,
        string $lastName,
        array $roles,
        Organization $organization,
    ): User {
        $user = (new User())
            ->setEmail($email)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setRoles($roles)
            ->setOrganization($organization);

        $user->setPassword(
            $this->passwordHasher->hashPassword($user, 'password')
        );

        return $user;
    }

    private function createAccessLog(
        User $user,
        Document $document,
        Organization $organization,
        string $action,
    ): AccessLog {
        return (new AccessLog())
            ->setUser($user)
            ->setDocument($document)
            ->setOrganization($organization)
            ->setAction($action)
            ->setIpAddress('127.0.0.1')
            ->setUserAgent('SecureFlow Fixture');
    }
}