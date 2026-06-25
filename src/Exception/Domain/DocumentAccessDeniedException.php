<?php

declare(strict_types=1);

namespace App\Exception\Domain;

use App\Entity\Document;
use App\Entity\User;

/**
 * Raised when a user attempts to access a document outside their allowed scope.
 */
final class DocumentAccessDeniedException extends BusinessRuleException
{
    public static function forUser(Document $document, User $user): self
    {
        return new self(sprintf(
            'User "%s" is not allowed to access document #%s.',
            $user->getUserIdentifier(),
            $document->getId() ?? 'new'
        ));
    }
}