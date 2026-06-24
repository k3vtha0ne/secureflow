<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController
{
    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        # On utilise_invoke quand un controller ne contient qu’une seule responsabilité / action
        # $controller = new HealthController(); contient directement la méthode __invoke() et peut être appelé comme une fonction
        return new JsonResponse([
            'status' => 'Ok',
            'application' => 'SecureFlow',
            'environment' => $_ENV['APP_ENV'] ?? 'unknown',
        ]);
    }
}