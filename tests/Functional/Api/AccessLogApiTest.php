<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\AccessLog;
use Symfony\Component\HttpFoundation\Response;

final class AccessLogApiTest extends ApiTestCase
{
    public function testAnonymousUserCannotReadGlobalAccessLogs(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/access-logs');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testAdminCanReadOrganizationAccessLogs(): void
    {
        $client = static::createClient();
        $uniqueSuffix = bin2hex(random_bytes(6));

        $organization = $this->createOrganization(sprintf('Audit Organization %s', $uniqueSuffix));
        $admin = $this->createUser($organization, sprintf('audit-admin-%s@example.test', $uniqueSuffix), ['ROLE_ADMIN']);
        $document = $this->createDocument($organization, $admin, sprintf('Audit Document %s', $uniqueSuffix));
        $this->createAccessLog($organization, $admin, $document, AccessLog::ACTION_VIEW);
        $this->flush();

        $token = $this->getJwtToken($client, $admin->getUserIdentifier());

        $client->request('GET', '/api/access-logs', server: $this->bearerHeaders($token));

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = $this->decodeJsonResponse($client);
        $logs = $payload['member'];

        self::assertNotEmpty($logs);
        self::assertSame(AccessLog::ACTION_VIEW, $logs[0]['action']);
        self::assertSame($document->getId(), $logs[0]['documentId']);
        self::assertSame($admin->getEmail(), $logs[0]['userEmail']);
        self::assertArrayHasKey('createdAt', $logs[0]);
        self::assertArrayNotHasKey('user', $logs[0]);
        self::assertArrayNotHasKey('document', $logs[0]);
        self::assertArrayNotHasKey('organization', $logs[0]);
    }

    public function testManagerCanReadOrganizationAccessLogs(): void
    {
        $client = static::createClient();
        $uniqueSuffix = bin2hex(random_bytes(6));

        $organization = $this->createOrganization(sprintf('Manager Audit Organization %s', $uniqueSuffix));
        $manager = $this->createUser($organization, sprintf('audit-manager-%s@example.test', $uniqueSuffix), ['ROLE_MANAGER']);
        $document = $this->createDocument($organization, $manager, sprintf('Manager Audit Document %s', $uniqueSuffix));
        $this->createAccessLog($organization, $manager, $document, AccessLog::ACTION_VIEW);
        $this->flush();

        $token = $this->getJwtToken($client, $manager->getUserIdentifier());

        $client->request('GET', '/api/access-logs', server: $this->bearerHeaders($token));

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = $this->decodeJsonResponse($client);

        self::assertNotEmpty($payload['member']);
        self::assertSame($manager->getEmail(), $payload['member'][0]['userEmail']);
    }

    public function testGlobalAccessLogsAreScopedByOrganization(): void
    {
        $client = static::createClient();
        $uniqueSuffix = bin2hex(random_bytes(6));

        $alphaOrganization = $this->createOrganization(sprintf('Alpha Audit Organization %s', $uniqueSuffix));
        $betaOrganization = $this->createOrganization(sprintf('Beta Audit Organization %s', $uniqueSuffix));
        $alphaAdmin = $this->createUser($alphaOrganization, sprintf('alpha-audit-admin-%s@example.test', $uniqueSuffix), ['ROLE_ADMIN']);
        $betaAdmin = $this->createUser($betaOrganization, sprintf('beta-audit-admin-%s@example.test', $uniqueSuffix), ['ROLE_ADMIN']);
        $alphaDocument = $this->createDocument($alphaOrganization, $alphaAdmin, sprintf('Alpha Audit Document %s', $uniqueSuffix));
        $betaDocument = $this->createDocument($betaOrganization, $betaAdmin, sprintf('Beta Audit Document %s', $uniqueSuffix));

        $this->createAccessLog($alphaOrganization, $alphaAdmin, $alphaDocument, AccessLog::ACTION_VIEW);
        $this->createAccessLog($betaOrganization, $betaAdmin, $betaDocument, AccessLog::ACTION_ARCHIVE);
        $this->flush();

        $token = $this->getJwtToken($client, $alphaAdmin->getUserIdentifier());

        $client->request('GET', '/api/access-logs', server: $this->bearerHeaders($token));

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = $this->decodeJsonResponse($client);
        $documentIds = array_column($payload['member'], 'documentId');

        self::assertContains($alphaDocument->getId(), $documentIds);
        self::assertNotContains($betaDocument->getId(), $documentIds);
    }

    public function testUserCannotReadGlobalAccessLogs(): void
    {
        $client = static::createClient();
        $uniqueSuffix = bin2hex(random_bytes(6));

        $organization = $this->createOrganization(sprintf('User Audit Organization %s', $uniqueSuffix));
        $user = $this->createUser($organization, sprintf('audit-user-%s@example.test', $uniqueSuffix), ['ROLE_USER']);
        $this->flush();

        $token = $this->getJwtToken($client, $user->getUserIdentifier());

        $client->request('GET', '/api/access-logs', server: $this->bearerHeaders($token));

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testAdminCanReadDocumentAccessLogs(): void
    {
        $client = static::createClient();
        $uniqueSuffix = bin2hex(random_bytes(6));

        $organization = $this->createOrganization(sprintf('Document Audit Organization %s', $uniqueSuffix));
        $admin = $this->createUser($organization, sprintf('document-audit-admin-%s@example.test', $uniqueSuffix), ['ROLE_ADMIN']);
        $document = $this->createDocument($organization, $admin, sprintf('Document Audit %s', $uniqueSuffix));
        $this->createAccessLog($organization, $admin, $document, AccessLog::ACTION_PUBLISH);
        $this->flush();

        $token = $this->getJwtToken($client, $admin->getUserIdentifier());

        $client->request(
            'GET',
            sprintf('/api/documents/%d/access-logs', $document->getId()),
            server: $this->bearerHeaders($token)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = $this->decodeJsonResponse($client);
        $logs = $payload['member'];

        self::assertCount(1, $logs);
        self::assertSame(AccessLog::ACTION_PUBLISH, $logs[0]['action']);
        self::assertSame($document->getId(), $logs[0]['documentId']);
        self::assertSame($admin->getEmail(), $logs[0]['userEmail']);
        self::assertArrayHasKey('createdAt', $logs[0]);
    }

    public function testDocumentAccessLogsReturnNotFoundForCrossTenantDocument(): void
    {
        $client = static::createClient();
        $uniqueSuffix = bin2hex(random_bytes(6));

        $alphaOrganization = $this->createOrganization(sprintf('Alpha Document Audit Organization %s', $uniqueSuffix));
        $betaOrganization = $this->createOrganization(sprintf('Beta Document Audit Organization %s', $uniqueSuffix));
        $alphaAdmin = $this->createUser($alphaOrganization, sprintf('alpha-document-audit-admin-%s@example.test', $uniqueSuffix), ['ROLE_ADMIN']);
        $betaAdmin = $this->createUser($betaOrganization, sprintf('beta-document-audit-admin-%s@example.test', $uniqueSuffix), ['ROLE_ADMIN']);
        $betaDocument = $this->createDocument($betaOrganization, $betaAdmin, sprintf('Beta Document Audit %s', $uniqueSuffix));
        $this->createAccessLog($betaOrganization, $betaAdmin, $betaDocument, AccessLog::ACTION_VIEW);
        $this->flush();

        $token = $this->getJwtToken($client, $alphaAdmin->getUserIdentifier());

        $client->request(
            'GET',
            sprintf('/api/documents/%d/access-logs', $betaDocument->getId()),
            server: $this->bearerHeaders($token)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testActionFilterLimitsGlobalAccessLogs(): void
    {
        $client = static::createClient();
        $uniqueSuffix = bin2hex(random_bytes(6));

        $organization = $this->createOrganization(sprintf('Filtered Audit Organization %s', $uniqueSuffix));
        $admin = $this->createUser($organization, sprintf('filtered-audit-admin-%s@example.test', $uniqueSuffix), ['ROLE_ADMIN']);
        $document = $this->createDocument($organization, $admin, sprintf('Filtered Audit Document %s', $uniqueSuffix));

        $this->createAccessLog($organization, $admin, $document, AccessLog::ACTION_VIEW);
        $this->createAccessLog($organization, $admin, $document, AccessLog::ACTION_ARCHIVE);
        $this->flush();

        $token = $this->getJwtToken($client, $admin->getUserIdentifier());

        $client->request('GET', '/api/access-logs?action=view', server: $this->bearerHeaders($token));

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $payload = $this->decodeJsonResponse($client);
        $actions = array_column($payload['member'], 'action');

        self::assertSame([AccessLog::ACTION_VIEW], $actions);
    }
}
