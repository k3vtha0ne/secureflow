<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\AccessLog;
use App\Entity\Document;
use App\Entity\User;
use App\Service\AuditTrailService;
use App\Service\DocumentAccessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsController]
final readonly class ViewDocumentController
{
    public function __construct(
        private Security $security,
        private DocumentAccessService $documentAccessService,
        private AuditTrailService $auditTrailService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(Document $document): JsonResponse
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        if (!$this->documentAccessService->canView($document, $user)) {
            throw new NotFoundHttpException('Document not found.');
        }

        $this->auditTrailService->recordDocumentAction(
            user: $user,
            document: $document,
            action: AccessLog::ACTION_VIEW
        );

        $this->entityManager->flush();

        return $this->jsonDocument($document);
    }

    private function jsonDocument(Document $document): JsonResponse
    {
        return new JsonResponse([
            'id' => $document->getId(),
            'title' => $document->getTitle(),
            'description' => $document->getDescription(),
            'status' => $document->getStatus(),
            'createdAt' => $document->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updatedAt' => $document->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ], Response::HTTP_OK);
    }
}
