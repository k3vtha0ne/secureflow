<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\Campaign;
use App\Entity\Document;
use App\Entity\Organization;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class ApiTestCase extends WebTestCase
{
    protected function entityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function passwordHasher(): UserPasswordHasherInterface
    {
        return static::getContainer()->get(UserPasswordHasherInterface::class);
    }

    protected function createOrganization(string $name): Organization
    {
        $organization = new Organization();
        $organization->setName($name);

        $this->entityManager()->persist($organization);

        return $organization;
    }

    protected function createUser(
        Organization $organization,
        string $email,
        array $roles = ['ROLE_USER'],
        string $plainPassword = 'password',
    ): User {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRoles($roles);
        $user->setOrganization($organization);
        $user->setPassword(
            $this->passwordHasher()->hashPassword($user, $plainPassword)
        );

        $this->entityManager()->persist($user);

        return $user;
    }

    protected function createDocument(
        Organization $organization,
        User $owner,
        string $title,
        ?\DateTimeImmutable $createdAt = null,
    ): Document {
        $createdAt ??= new \DateTimeImmutable();

        $document = new Document();
        $document->setTitle($title);
        $document->setDescription(sprintf('%s description.', $title));
        $document->setStatus(Document::STATUS_PUBLISHED);
        $document->setStoragePath(sprintf('/documents/%s.pdf', md5($title)));
        $document->setOwner($owner);
        $document->setOrganization($organization);
        $document->setIsDeleted(false);
        $document->setCreatedAt($createdAt);

        $this->entityManager()->persist($document);

        return $document;
    }

    protected function createCampaign(
        Organization $organization,
        User $createdBy,
        string $name,
        ?\DateTimeImmutable $createdAt = null,
        string $status = Campaign::STATUS_SCHEDULED,
    ): Campaign {
        $createdAt ??= new \DateTimeImmutable();

        $campaign = new Campaign();
        $campaign->setName($name);
        $campaign->setDescription(sprintf('%s description.', $name));
        $campaign->setStatus($status);
        $campaign->setScheduledAt($createdAt->modify('+1 day'));
        $campaign->setOrganization($organization);
        $campaign->setCreatedBy($createdBy);
        $campaign->setCreatedAt($createdAt);

        $this->entityManager()->persist($campaign);

        return $campaign;
    }

    protected function flush(): void
    {
        $this->entityManager()->flush();
    }

    protected function getJwtToken(KernelBrowser $client, string $email, string $password = 'password'): string
    {
        $client->request(
            'POST',
            '/api/login_check',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $email,
                'password' => $password,
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();

        $payload = $this->decodeJsonResponse($client);

        self::assertArrayHasKey('token', $payload);
        self::assertIsString($payload['token']);
        self::assertNotSame('', $payload['token']);

        return $payload['token'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeJsonResponse(KernelBrowser $client): array
    {
        return json_decode(
            $client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    /**
     * @return array<string, string>
     */
    protected function bearerHeaders(string $token, array $headers = []): array
    {
        return array_merge([
            'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
        ], $headers);
    }
}
