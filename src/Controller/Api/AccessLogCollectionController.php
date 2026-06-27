<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\AccessLog;
use App\Entity\Document;
use App\Entity\Organization;
use App\Entity\User;
use App\Repository\AccessLogRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class AccessLogCollectionController
{
    private const DEFAULT_LIMIT = 50;

    public function __construct(
        private Security $security,
        private AccessLogRepository $accessLogRepository,
    ) {
    }

    #[Route('/api/access-logs', name: 'api_access_log_collection', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        if (!$this->canReadAuditLogs()) {
            throw new AccessDeniedHttpException('Audit logs require an administrator or manager role.');
        }

        $organization = $user->getOrganization();

        if (!$organization instanceof Organization) {
            throw new AccessDeniedHttpException('Authenticated user has no organization.');
        }

        $accessLogs = $this->accessLogRepository->findRecentByOrganization(
            organization: $organization,
            limit: self::DEFAULT_LIMIT,
            action: $this->queryAction($request),
        );

        return new JsonResponse([
            'member' => array_map($this->serializeAccessLog(...), $accessLogs),
        ], Response::HTTP_OK);
    }

    private function canReadAuditLogs(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN')
            || $this->security->isGranted('ROLE_MANAGER');
    }

    private function queryAction(Request $request): ?string
    {
        $action = $request->query->getString('action');

        return '' === $action ? null : $action;
    }

    /**
     * @return array{
     *     id: int|null,
     *     action: string|null,
     *     documentId: int|null,
     *     documentTitle: string|null,
     *     userId: int|null,
     *     userEmail: string|null,
     *     ipAddress: string|null,
     *     userAgent: string|null,
     *     createdAt: string|null
     * }
     */
    private function serializeAccessLog(AccessLog $accessLog): array
    {
        $document = $accessLog->getDocument();
        $user = $accessLog->getUser();

        return [
            'id' => $accessLog->getId(),
            'action' => $accessLog->getAction(),
            'documentId' => $document instanceof Document ? $document->getId() : null,
            'documentTitle' => $document instanceof Document ? $document->getTitle() : null,
            'userId' => $user instanceof User ? $user->getId() : null,
            'userEmail' => $user instanceof User ? $user->getEmail() : null,
            'ipAddress' => $accessLog->getIpAddress(),
            'userAgent' => $accessLog->getUserAgent(),
            'createdAt' => $accessLog->getCreatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
