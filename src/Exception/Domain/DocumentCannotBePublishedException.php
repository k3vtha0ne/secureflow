<?php

declare(strict_types=1);

namespace App\Exception\Domain;

use App\Entity\Document;

/**
 * Raised when a document cannot be published because its lifecycle state
 * does not allow it.
 */
final class DocumentCannotBePublishedException extends BusinessRuleException
{
    public static function forDocument(Document $document): self
    {
        return new self(sprintf(
            'Document #%s cannot be published from status "%s".',
            $document->getId() ?? 'new',
            $document->getStatus() ?? 'unknown'
        ));
    }
}