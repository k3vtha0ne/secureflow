<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use Symfony\Component\HttpFoundation\Response;

final class DocumentApiTest extends ApiTestCase
{
    public function testDocumentsCollectionIsScopedByUserOrganization(): void
    {
        $client = static::createClient();

        $uniqueSuffix = bin2hex(random_bytes(6));

        $alphaOrganization = $this->createOrganization(sprintf('Alpha Document Organization %s', $uniqueSuffix));
        $betaOrganization = $this->createOrganization(sprintf('Beta Document Organization %s', $uniqueSuffix));

        $alphaUser = $this->createUser(
            $alphaOrganization,
            sprintf('alpha-document-%s@example.test', $uniqueSuffix),
            ['ROLE_ADMIN']
        );

        $betaUser = $this->createUser(
            $betaOrganization,
            sprintf('beta-document-%s@example.test', $uniqueSuffix),
            ['ROLE_USER']
        );

        $alphaDocument = $this->createDocument(
            $alphaOrganization,
            $alphaUser,
            sprintf('Alpha Document %s', $uniqueSuffix)
        );

        $betaDocument = $this->createDocument(
            $betaOrganization,
            $betaUser,
            sprintf('Beta Document %s', $uniqueSuffix)
        );

        $this->flush();

        $alphaToken = $this->getJwtToken($client, $alphaUser->getUserIdentifier());

        $client->request(
            'GET',
            '/api/documents',
            server: $this->bearerHeaders($alphaToken)
        );

        self::assertResponseIsSuccessful();

        $payload = $this->decodeJsonResponse($client);
        $documents = $payload['member'] ?? $payload['hydra:member'] ?? [];
        $titles = array_column($documents, 'title');

        self::assertContains($alphaDocument->getTitle(), $titles);
        self::assertNotContains($betaDocument->getTitle(), $titles);
    }

    public function testDocumentItemAccessIsScopedByUserOrganization(): void
    {
        $client = static::createClient();

        $uniqueSuffix = bin2hex(random_bytes(6));

        $alphaOrganization = $this->createOrganization(sprintf('Alpha Document Item Organization %s', $uniqueSuffix));
        $betaOrganization = $this->createOrganization(sprintf('Beta Document Item Organization %s', $uniqueSuffix));

        $alphaUser = $this->createUser(
            $alphaOrganization,
            sprintf('alpha-document-item-%s@example.test', $uniqueSuffix),
            ['ROLE_ADMIN']
        );

        $betaUser = $this->createUser(
            $betaOrganization,
            sprintf('beta-document-item-%s@example.test', $uniqueSuffix),
            ['ROLE_USER']
        );

        $alphaDocument = $this->createDocument(
            $alphaOrganization,
            $alphaUser,
            sprintf('Alpha Item Document %s', $uniqueSuffix)
        );

        $betaDocument = $this->createDocument(
            $betaOrganization,
            $betaUser,
            sprintf('Beta Item Document %s', $uniqueSuffix)
        );

        $this->flush();

        $alphaToken = $this->getJwtToken($client, $alphaUser->getUserIdentifier());
        $betaToken = $this->getJwtToken($client, $betaUser->getUserIdentifier());

        $client->request(
            'GET',
            sprintf('/api/documents/%d', $alphaDocument->getId()),
            server: $this->bearerHeaders($alphaToken)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->request(
            'GET',
            sprintf('/api/documents/%d', $betaDocument->getId()),
            server: $this->bearerHeaders($betaToken)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->request(
            'GET',
            sprintf('/api/documents/%d', $betaDocument->getId()),
            server: $this->bearerHeaders($alphaToken)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $client->request(
            'GET',
            sprintf('/api/documents/%d', $alphaDocument->getId()),
            server: $this->bearerHeaders($betaToken)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDocumentCreationIsNotExposedThroughApi(): void
    {
        $client = static::createClient();

        $uniqueSuffix = bin2hex(random_bytes(6));

        $organization = $this->createOrganization(sprintf('Read Only Document Organization %s', $uniqueSuffix));

        $user = $this->createUser(
            $organization,
            sprintf('readonly-document-%s@example.test', $uniqueSuffix),
            ['ROLE_ADMIN']
        );

        $this->flush();

        $token = $this->getJwtToken($client, $user->getUserIdentifier());

        $client->request(
            'POST',
            '/api/documents',
            server: $this->bearerHeaders($token, [
                'CONTENT_TYPE' => 'application/json',
            ]),
            content: json_encode([
                'title' => 'Client-created document',
                'status' => 'published',
                'organization' => '/api/organizations/1',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }
}
