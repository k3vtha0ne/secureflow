<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Document;
use App\Entity\User;

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
        if (null === $user->getOrganization()) {
            return false;
        }

        return $document->getOrganization()?->getId() === $user->getOrganization()->getId();
    }
}