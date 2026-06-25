<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Document;
use App\Exception\Domain\DocumentCannotBeArchivedException;
use App\Exception\Domain\DocumentCannotBePublishedException;
use App\Service\DocumentLifecycleService;
use PHPUnit\Framework\TestCase;

final class DocumentLifecycleServiceTest extends TestCase
{
    private DocumentLifecycleService $service;

    protected function setUp(): void
    {
        $this->service = new DocumentLifecycleService();
    }

    public function testDraftDocumentCanBePublished(): void
    {
        $document = new Document();
        $document->setStatus(Document::STATUS_DRAFT);
        $document->setIsDeleted(false);

        self::assertTrue($this->service->canPublish($document));
    }

    public function testPublishedDocumentCannotBePublishedAgain(): void
    {
        $document = new Document();
        $document->setStatus(Document::STATUS_PUBLISHED);
        $document->setIsDeleted(false);

        self::assertFalse($this->service->canPublish($document));
    }

    public function testDeletedDraftDocumentCannotBePublished(): void
    {
        $document = new Document();
        $document->setStatus(Document::STATUS_DRAFT);
        $document->setIsDeleted(true);

        self::assertFalse($this->service->canPublish($document));
    }

    public function testPublishedDocumentCanBeArchived(): void
    {
        $document = new Document();
        $document->setStatus(Document::STATUS_PUBLISHED);
        $document->setIsDeleted(false);

        self::assertTrue($this->service->canArchive($document));
    }

    public function testDraftDocumentCannotBeArchived(): void
    {
        $document = new Document();
        $document->setStatus(Document::STATUS_DRAFT);
        $document->setIsDeleted(false);

        self::assertFalse($this->service->canArchive($document));
    }

    public function testDeletedPublishedDocumentCannotBeArchived(): void
    {
        $document = new Document();
        $document->setStatus(Document::STATUS_PUBLISHED);
        $document->setIsDeleted(true);

        self::assertFalse($this->service->canArchive($document));
    }

    public function testNonDeletedDocumentCanBeDeleted(): void
    {
        $document = new Document();
        $document->setIsDeleted(false);

        self::assertTrue($this->service->canDelete($document));
    }

    public function testAlreadyDeletedDocumentCannotBeDeletedAgain(): void
    {
        $document = new Document();
        $document->setIsDeleted(true);

        self::assertFalse($this->service->canDelete($document));
    }

    public function testDenyUnlessCanPublishDoesNotThrowWhenDocumentCanBePublished(): void
    {
        $document = new Document();
        $document->setStatus(Document::STATUS_DRAFT);
        $document->setIsDeleted(false);

        $this->service->denyUnlessCanPublish($document);

        self::assertTrue(true);
    }

    public function testDenyUnlessCanPublishThrowsWhenDocumentCannotBePublished(): void
    {
        $document = new Document();
        $document->setStatus(Document::STATUS_ARCHIVED);
        $document->setIsDeleted(false);

        $this->expectException(DocumentCannotBePublishedException::class);

        $this->service->denyUnlessCanPublish($document);
    }

    public function testDenyUnlessCanArchiveDoesNotThrowWhenDocumentCanBeArchived(): void
    {
        $document = new Document();
        $document->setStatus(Document::STATUS_PUBLISHED);
        $document->setIsDeleted(false);

        $this->service->denyUnlessCanArchive($document);

        self::assertTrue(true);
    }

    public function testDenyUnlessCanArchiveThrowsWhenDocumentCannotBeArchived(): void
    {
        $document = new Document();
        $document->setStatus(Document::STATUS_DRAFT);
        $document->setIsDeleted(false);

        $this->expectException(DocumentCannotBeArchivedException::class);

        $this->service->denyUnlessCanArchive($document);
    }
}