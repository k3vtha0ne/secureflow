<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Document;
use App\Entity\Organization;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ApiSecurityTest extends WebTestCase
{
    public function testDocumentsCollectionRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/documents');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCampaignsCollectionRequiresAuthentication(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/campaigns');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testValidJwtLoginReturnsToken(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $uniqueSuffix = bin2hex(random_bytes(6));
        $email = sprintf('jwt-login-%s@example.test', $uniqueSuffix);

        $organization = new Organization();
        $organization->setName(sprintf('Test Organization %s', $uniqueSuffix));

        $user = new User();
        $user->setEmail($email);
        $user->setFirstName('Jwt');
        $user->setLastName('Login');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setOrganization($organization);
        $user->setPassword(
            $passwordHasher->hashPassword($user, 'password')
        );

        $entityManager->persist($organization);
        $entityManager->persist($user);
        $entityManager->flush();

        $client->request(
            'POST',
            '/api/login_check',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $email,
                'password' => 'password',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();

        $payload = json_decode(
            $client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertArrayHasKey('token', $payload);
        self::assertIsString($payload['token']);
        self::assertNotSame('', $payload['token']);
    }

    private function createUser(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        Organization $organization,
        string $email,
        array $roles = ['ROLE_USER'],
    ): User {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRoles($roles);
        $user->setOrganization($organization);
        $user->setPassword(
            $passwordHasher->hashPassword($user, 'password')
        );

        $entityManager->persist($user);

        return $user;
    }

    private function getJwtToken(KernelBrowser $client, string $email): string
    {
        $client->request(
            'POST',
            '/api/login_check',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $email,
                'password' => 'password',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();

        $payload = json_decode(
            $client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertArrayHasKey('token', $payload);

        return $payload['token'];
    }  

    public function testDocumentsCollectionIsScopedByUserOrganization(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $uniqueSuffix = bin2hex(random_bytes(6));

        $alphaOrganization = new Organization();
        $alphaOrganization->setName(sprintf('Alpha Organization %s', $uniqueSuffix));

        $betaOrganization = new Organization();
        $betaOrganization->setName(sprintf('Beta Organization %s', $uniqueSuffix));

        $entityManager->persist($alphaOrganization);
        $entityManager->persist($betaOrganization);

        $alphaUser = $this->createUser(
            $entityManager,
            $passwordHasher,
            $alphaOrganization,
            sprintf('alpha-%s@example.test', $uniqueSuffix),
            ['ROLE_ADMIN']
        );

        $betaUser = $this->createUser(
            $entityManager,
            $passwordHasher,
            $betaOrganization,
            sprintf('beta-%s@example.test', $uniqueSuffix),
            ['ROLE_USER']
        );

        $now = new \DateTimeImmutable();

        $alphaDocument = new Document();
        $alphaDocument->setTitle(sprintf('Alpha Document %s', $uniqueSuffix));
        $alphaDocument->setDescription('Visible only by Alpha organization.');
        $alphaDocument->setStatus('published');
        $alphaDocument->setStoragePath(sprintf('/documents/alpha-%s.pdf', $uniqueSuffix));
        $alphaDocument->setOwner($alphaUser);
        $alphaDocument->setOrganization($alphaOrganization);
        $alphaDocument->setIsDeleted(false);
        $alphaDocument->setCreatedAt($now);

        $betaDocument = new Document();
        $betaDocument->setTitle(sprintf('Beta Document %s', $uniqueSuffix));
        $betaDocument->setDescription('Visible only by Beta organization.');
        $betaDocument->setStatus('published');
        $betaDocument->setStoragePath(sprintf('/documents/beta-%s.pdf', $uniqueSuffix));
        $betaDocument->setOwner($betaUser);
        $betaDocument->setOrganization($betaOrganization);
        $betaDocument->setIsDeleted(false);
        $betaDocument->setCreatedAt($now);

        $entityManager->persist($alphaDocument);
        $entityManager->persist($betaDocument);
        $entityManager->flush();

        $alphaToken = $this->getJwtToken($client, $alphaUser->getUserIdentifier());

        $client->request(
            'GET',
            '/api/documents',
            server: [
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $alphaToken),
            ]
        );

        self::assertResponseIsSuccessful();

        $payload = json_decode(
            $client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $documents = $payload['member'] ?? $payload['hydra:member'] ?? [];

        $titles = array_column($documents, 'title');

        self::assertContains($alphaDocument->getTitle(), $titles);
        self::assertNotContains($betaDocument->getTitle(), $titles);
    }

    

}