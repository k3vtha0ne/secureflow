<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Document;
use App\Entity\User;
use App\Exception\Domain\DocumentCannotBeArchivedException;
use App\Service\DocumentAccessService;
use App\Service\DocumentLifecycleService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsController]
final readonly class ArchiveDocumentController
{
    public function __construct(
        private Security $security,
        private DocumentAccessService $documentAccessService,
        private DocumentLifecycleService $documentLifecycleService,
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

        try {
            $this->documentLifecycleService->denyUnlessCanArchive($document);
        } catch (DocumentCannotBeArchivedException $exception) {
            throw new ConflictHttpException($exception->getMessage(), $exception);
        }

        $document->setStatus(Document::STATUS_ARCHIVED);
        $document->setUpdatedAt(new \DateTimeImmutable());

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
