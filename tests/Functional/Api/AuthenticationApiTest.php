<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use Symfony\Component\HttpFoundation\Response;

final class AuthenticationApiTest extends ApiTestCase
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

        $uniqueSuffix = bin2hex(random_bytes(6));

        $organization = $this->createOrganization(sprintf('JWT Login Organization %s', $uniqueSuffix));

        $user = $this->createUser(
            $organization,
            sprintf('jwt-login-%s@example.test', $uniqueSuffix),
            ['ROLE_ADMIN']
        );

        $this->flush();

        $client->request(
            'POST',
            '/api/login_check',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $user->getUserIdentifier(),
                'password' => 'password',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();

        $payload = $this->decodeJsonResponse($client);

        self::assertArrayHasKey('token', $payload);
        self::assertIsString($payload['token']);
        self::assertNotSame('', $payload['token']);
    }

    public function testInvalidJwtLoginIsRejected(): void
    {
        $client = static::createClient();

        $uniqueSuffix = bin2hex(random_bytes(6));

        $organization = $this->createOrganization(sprintf('Invalid Login Organization %s', $uniqueSuffix));

        $user = $this->createUser(
            $organization,
            sprintf('invalid-login-%s@example.test', $uniqueSuffix),
            ['ROLE_USER']
        );

        $this->flush();

        $client->request(
            'POST',
            '/api/login_check',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $user->getUserIdentifier(),
                'password' => 'wrong-password',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testDocumentsCollectionRejectsInvalidJwtToken(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/documents',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer invalid-token',
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
