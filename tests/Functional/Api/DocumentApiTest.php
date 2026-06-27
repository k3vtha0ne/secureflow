<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\AccessLog;
use App\Entity\Document;
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

    public function testDraftDocumentCanBePublishedThroughBusinessAction(): void
    {
        $client = static::createClient();

        $uniqueSuffix = bin2hex(random_bytes(6));

        $organization = $this->createOrganization(sprintf('Publish Document Organization %s', $uniqueSuffix));

        $user = $this->createUser(
            $organization,
            sprintf('publish-document-%s@example.test', $uniqueSuffix),
            ['ROLE_ADMIN']
        );

        $document = $this->createDocument(
            $organization,
            $user,
            sprintf('Draft Document %s', $uniqueSuffix)
        );
        $document->setStatus(Document::STATUS_DRAFT);

        $this->flush();

        $token = $this->getJwtToken($client, $user->getUserIdentifier());

        $client->request(
            'POST',
            sprintf('/api/documents/%d/publish', $document->getId()),
            server: $this->bearerHeaders($token)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = $this->decodeJsonResponse($client);

        self::assertSame(Document::STATUS_PUBLISHED, $payload['status']);

        $this->entityManager()->clear();

        $storedDocument = $this->entityManager()
            ->getRepository(Document::class)
            ->find($document->getId());

        self::assertInstanceOf(Document::class, $storedDocument);
        self::assertSame(Document::STATUS_PUBLISHED, $storedDocument->getStatus());
        self::assertInstanceOf(\DateTimeImmutable::class, $storedDocument->getUpdatedAt());
    }

    public function testPublishedDocumentCanBeArchivedThroughBusinessAction(): void
    {
        $client = static::createClient();

        $uniqueSuffix = bin2hex(random_bytes(6));

        $organization = $this->createOrganization(sprintf('Archive Document Organization %s', $uniqueSuffix));

        $user = $this->createUser(
            $organization,
            sprintf('archive-document-%s@example.test', $uniqueSuffix),
            ['ROLE_ADMIN']
        );

        $document = $this->createDocument(
            $organization,
            $user,
            sprintf('Published Document %s', $uniqueSuffix)
        );

        $this->flush();

        $token = $this->getJwtToken($client, $user->getUserIdentifier());

        $client->request(
            'POST',
            sprintf('/api/documents/%d/archive', $document->getId()),
            server: $this->bearerHeaders($token)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = $this->decodeJsonResponse($client);

        self::assertSame(Document::STATUS_ARCHIVED, $payload['status']);

        $this->entityManager()->clear();

        $storedDocument = $this->entityManager()
            ->getRepository(Document::class)
            ->find($document->getId());

        self::assertInstanceOf(Document::class, $storedDocument);
        self::assertSame(Document::STATUS_ARCHIVED, $storedDocument->getStatus());
        self::assertInstanceOf(\DateTimeImmutable::class, $storedDocument->getUpdatedAt());
    }

    public function testPublishedDocumentCannotBePublishedAgain(): void
    {
        $client = static::createClient();

        $uniqueSuffix = bin2hex(random_bytes(6));

        $organization = $this->createOrganization(sprintf('Invalid Publish Organization %s', $uniqueSuffix));

        $user = $this->createUser(
            $organization,
            sprintf('invalid-publish-document-%s@example.test', $uniqueSuffix),
            ['ROLE_ADMIN']
        );

        $document = $this->createDocument(
            $organization,
            $user,
            sprintf('Already Published Document %s', $uniqueSuffix)
        );

        $this->flush();

        $token = $this->getJwtToken($client, $user->getUserIdentifier());

        $client->request(
            'POST',
            sprintf('/api/documents/%d/publish', $document->getId()),
            server: $this->bearerHeaders($token)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    public function testDocumentLifecycleActionIsScopedByUserOrganization(): void
    {
        $client = static::createClient();

        $uniqueSuffix = bin2hex(random_bytes(6));

        $alphaOrganization = $this->createOrganization(sprintf('Alpha Lifecycle Organization %s', $uniqueSuffix));
        $betaOrganization = $this->createOrganization(sprintf('Beta Lifecycle Organization %s', $uniqueSuffix));

        $alphaUser = $this->createUser(
            $alphaOrganization,
            sprintf('alpha-lifecycle-%s@example.test', $uniqueSuffix),
            ['ROLE_ADMIN']
        );

        $betaUser = $this->createUser(
            $betaOrganization,
            sprintf('beta-lifecycle-%s@example.test', $uniqueSuffix),
            ['ROLE_USER']
        );

        $betaDocument = $this->createDocument(
            $betaOrganization,
            $betaUser,
            sprintf('Beta Lifecycle Document %s', $uniqueSuffix)
        );
        $betaDocument->setStatus(Document::STATUS_DRAFT);

        $this->flush();

        $alphaToken = $this->getJwtToken($client, $alphaUser->getUserIdentifier());

        $client->request(
            'POST',
            sprintf('/api/documents/%d/publish', $betaDocument->getId()),
            server: $this->bearerHeaders($alphaToken)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }


    public function testDocumentItemViewCreatesAccessLog(): void
    {
        $client = static::createClient();

        $uniqueSuffix = bin2hex(random_bytes(6));

        $organization = $this->createOrganization(sprintf('View Audit Organization %s', $uniqueSuffix));

        $user = $this->createUser(
            $organization,
            sprintf('view-audit-%s@example.test', $uniqueSuffix),
            ['ROLE_ADMIN']
        );

        $document = $this->createDocument(
            $organization,
            $user,
            sprintf('View Audit Document %s', $uniqueSuffix)
        );

        $this->flush();

        $token = $this->getJwtToken($client, $user->getUserIdentifier());

        $client->request(
            'GET',
            sprintf('/api/documents/%d', $document->getId()),
            server: $this->bearerHeaders($token)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $accessLog = $this->entityManager()
            ->getRepository(AccessLog::class)
            ->findOneBy([
                'user' => $user,
                'document' => $document,
                'organization' => $organization,
                'action' => AccessLog::ACTION_VIEW,
            ]);

        self::assertInstanceOf(AccessLog::class, $accessLog);
        self::assertInstanceOf(\DateTimeImmutable::class, $accessLog->getCreatedAt());
    }

    public function testPublishDocumentActionCreatesAccessLog(): void
    {
        $client = static::createClient();

        $uniqueSuffix = bin2hex(random_bytes(6));

        $organization = $this->createOrganization(sprintf('Publish Audit Organization %s', $uniqueSuffix));

        $user = $this->createUser(
            $organization,
            sprintf('publish-audit-%s@example.test', $uniqueSuffix),
            ['ROLE_ADMIN']
        );

        $document = $this->createDocument(
            $organization,
            $user,
            sprintf('Publish Audit Document %s', $uniqueSuffix)
        );
        $document->setStatus(Document::STATUS_DRAFT);

        $this->flush();

        $token = $this->getJwtToken($client, $user->getUserIdentifier());

        $client->request(
            'POST',
            sprintf('/api/documents/%d/publish', $document->getId()),
            server: $this->bearerHeaders($token)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $accessLog = $this->entityManager()
            ->getRepository(AccessLog::class)
            ->findOneBy([
                'user' => $user,
                'document' => $document,
                'organization' => $organization,
                'action' => AccessLog::ACTION_PUBLISH,
            ]);

        self::assertInstanceOf(AccessLog::class, $accessLog);
        self::assertInstanceOf(\DateTimeImmutable::class, $accessLog->getCreatedAt());
    }

    public function testArchiveDocumentActionCreatesAccessLog(): void
    {
        $client = static::createClient();

        $uniqueSuffix = bin2hex(random_bytes(6));

        $organization = $this->createOrganization(sprintf('Archive Audit Organization %s', $uniqueSuffix));

        $user = $this->createUser(
            $organization,
            sprintf('archive-audit-%s@example.test', $uniqueSuffix),
            ['ROLE_ADMIN']
        );

        $document = $this->createDocument(
            $organization,
            $user,
            sprintf('Archive Audit Document %s', $uniqueSuffix)
        );

        $this->flush();

        $token = $this->getJwtToken($client, $user->getUserIdentifier());

        $client->request(
            'POST',
            sprintf('/api/documents/%d/archive', $document->getId()),
            server: $this->bearerHeaders($token)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $accessLog = $this->entityManager()
            ->getRepository(AccessLog::class)
            ->findOneBy([
                'user' => $user,
                'document' => $document,
                'organization' => $organization,
                'action' => AccessLog::ACTION_ARCHIVE,
            ]);

        self::assertInstanceOf(AccessLog::class, $accessLog);
        self::assertInstanceOf(\DateTimeImmutable::class, $accessLog->getCreatedAt());
    }

}
