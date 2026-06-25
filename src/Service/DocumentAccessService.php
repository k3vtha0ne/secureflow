<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Document;
use App\Entity\User;
use App\Exception\Domain\DocumentAccessDeniedException;

/**
 * Centralizes business access rules for Document entities.
 *
 * Security voters should delegate to this service instead of carrying
 * domain rules directly. This keeps access logic reusable and easier to test.
 */
final class DocumentAccessService
{
    public function canView(Document $document, User $user): bool
    {
        $userOrganization = $user->getOrganization();
        $documentOrganization = $document->getOrganization();

        if (null === $userOrganization || null === $documentOrganization) {
            return false;
        }

        if ($userOrganization === $documentOrganization) {
            return true;
        }

        if (null === $userOrganization->getId() || null === $documentOrganization->getId()) {
            return false;
        }

        return $documentOrganization->getId() === $userOrganization->getId();
    }

    public function denyUnlessCanView(Document $document, User $user): void
    {
        if (!$this->canView($document, $user)) {
            throw DocumentAccessDeniedException::forUser($document, $user);
        }
    }
}