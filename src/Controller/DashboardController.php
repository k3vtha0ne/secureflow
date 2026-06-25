<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'vite_assets' => $this->getViteAssets(),
        ]);
    }

    /**
     * @return array{js: list<string>, css: list<string>}
     */
    private function getViteAssets(): array
    {
        $manifestPath = $this->projectDir.'/public/build/.vite/manifest.json';

        if (!is_file($manifestPath)) {
            return [
                'js' => [],
                'css' => [],
            ];
        }

        $manifest = json_decode(
            file_get_contents($manifestPath),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $entry = $manifest['assets/react/main.jsx'] ?? null;

        if (!is_array($entry)) {
            return [
                'js' => [],
                'css' => [],
            ];
        }

        $js = isset($entry['file']) ? ['/build/'.$entry['file']] : [];

        $css = array_map(
            static fn (string $file): string => '/build/'.$file,
            $entry['css'] ?? []
        );

        return [
            'js' => $js,
            'css' => $css,
        ];
    }
}
