<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Document;
use App\Exception\Domain\DocumentCannotBeArchivedException;
use App\Exception\Domain\DocumentCannotBePublishedException;

/**
 * Centralizes document lifecycle rules.
 *
 * This service answers domain questions such as:
 * - can this document be published?
 * - can this document be archived?
 * - should a lifecycle transition be blocked by a business rule?
 */
final class DocumentLifecycleService
{
    public function canPublish(Document $document): bool
    {
        return Document::STATUS_DRAFT === $document->getStatus()
            && false === $document->isDeleted();
    }

    public function canArchive(Document $document): bool
    {
        return Document::STATUS_PUBLISHED === $document->getStatus()
            && false === $document->isDeleted();
    }

    public function canDelete(Document $document): bool
    {
        return false === $document->isDeleted();
    }

    public function denyUnlessCanPublish(Document $document): void
    {
        if (!$this->canPublish($document)) {
            throw DocumentCannotBePublishedException::forDocument($document);
        }
    }

    public function denyUnlessCanArchive(Document $document): void
    {
        if (!$this->canArchive($document)) {
            throw DocumentCannotBeArchivedException::forDocument($document);
        }
    }
}