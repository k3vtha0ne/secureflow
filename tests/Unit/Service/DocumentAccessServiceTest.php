<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Document;
use App\Entity\Organization;
use App\Entity\User;
use App\Service\DocumentAccessService;
use PHPUnit\Framework\TestCase;

final class DocumentAccessServiceTest extends TestCase
{
    private DocumentAccessService $service;

    protected function setUp(): void
    {
        $this->service = new DocumentAccessService();
    }

    public function testUserCanViewDocumentFromSameOrganization(): void
    {
        $organization = new Organization();
        $organization->setName('Alpha');

        $user = new User();
        $user->setOrganization($organization);

        $document = new Document();
        $document->setOrganization($organization);

        self::assertTrue($this->service->canView($document, $user));
    }

    public function testUserCannotViewDocumentFromAnotherOrganization(): void
    {
        $userOrganization = new Organization();
        $userOrganization->setName('Alpha');

        $documentOrganization = new Organization();
        $documentOrganization->setName('Beta');

        $user = new User();
        $user->setOrganization($userOrganization);

        $document = new Document();
        $document->setOrganization($documentOrganization);

        self::assertFalse($this->service->canView($document, $user));
    }

    public function testUserWithoutOrganizationCannotViewDocument(): void
    {
        $documentOrganization = new Organization();
        $documentOrganization->setName('Alpha');

        $user = new User();

        $document = new Document();
        $document->setOrganization($documentOrganization);

        self::assertFalse($this->service->canView($document, $user));
    }
}