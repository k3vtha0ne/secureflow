<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Organization;
use App\Entity\User;
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
}