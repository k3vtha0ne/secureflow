<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AccessLog;
use App\Entity\Document;
use App\Entity\Organization;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class AuditTrailService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
    ) {
    }

    public function recordDocumentAction(User $user, Document $document, string $action): AccessLog
    {
        $organization = $document->getOrganization();

        if (!$organization instanceof Organization) {
            throw new \LogicException('Cannot create an access log for a document without organization.');
        }

        $request = $this->requestStack->getCurrentRequest();

        $accessLog = (new AccessLog())
            ->setUser($user)
            ->setDocument($document)
            ->setOrganization($organization)
            ->setAction($action)
            ->setIpAddress($request?->getClientIp())
            ->setUserAgent($request?->headers->get('User-Agent'));

        $this->entityManager->persist($accessLog);

        return $accessLog;
    }
}
