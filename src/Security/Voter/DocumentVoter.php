<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Document;
use App\Entity\User;
use App\Service\DocumentAccessService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Centralizes access rules for Document resources.
 *
 * The API query extension already prevents cross-organization reads at database level.
 * This voter is a second security layer for any code path that checks permissions
 * after a Document entity has been loaded.
 */
final class DocumentVoter extends Voter
{
    public const VIEW = 'DOCUMENT_VIEW';

    public function __construct(
        private readonly DocumentAccessService $documentAccessService,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::VIEW === $attribute && $subject instanceof Document;
    }

    /**
     * @param Document $subject
     */
    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->documentAccessService->canView($subject, $user),
            default => false,
        };
    }

}