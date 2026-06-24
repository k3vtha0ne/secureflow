<?php

namespace App\Command;

use App\Repository\AccessLogRepository;
use App\Repository\CampaignRepository;
use App\Repository\DocumentRepository;
use App\Repository\OrganizationRepository;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:report:domain-overview',
    description: 'Displays a domain overview based on organizations, documents, campaigns and access logs.',
)]
final class DomainOverviewCommand extends Command
{
    public function __construct(
        private readonly OrganizationRepository $organizationRepository,
        private readonly UserRepository $userRepository,
        private readonly DocumentRepository $documentRepository,
        private readonly CampaignRepository $campaignRepository,
        private readonly AccessLogRepository $accessLogRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $organizations = $this->organizationRepository->findAll();

        if ($organizations === []) {
            $io->warning('No organization found.');

            return Command::SUCCESS;
        }

        $io->title('SecureFlow domain overview');

        foreach ($organizations as $organization) {
            $io->section($organization->getName());

            $userCount = $this->userRepository->countByOrganization($organization);
            $documentCount = $this->documentRepository->countNotDeletedByOrganization($organization);
            $campaignStats = $this->campaignRepository->countByStatusForOrganization($organization);
            $actionStats = $this->accessLogRepository->countActionsByOrganization($organization);
            $documentAccessStats = $this->accessLogRepository->countAccessesByDocumentForOrganization($organization);

            $io->definitionList(
                ['Users' => $userCount],
                ['Active documents' => $documentCount],
            );

            if ($campaignStats !== []) {
                $table = new Table($output);
                $table
                    ->setHeaderTitle('Campaigns by status')
                    ->setHeaders(['Status', 'Total'])
                    ->setRows(array_map(
                        static fn (array $row): array => [$row['status'], $row['total']],
                        $campaignStats
                    ))
                    ->render();
            }

            if ($actionStats !== []) {
                $table = new Table($output);
                $table
                    ->setHeaderTitle('Access logs by action')
                    ->setHeaders(['Action', 'Total'])
                    ->setRows(array_map(
                        static fn (array $row): array => [$row['action'], $row['total']],
                        $actionStats
                    ))
                    ->render();
            }

            if ($documentAccessStats !== []) {
                $table = new Table($output);
                $table
                    ->setHeaderTitle('Accesses by document')
                    ->setHeaders(['Document ID', 'Document title', 'Access count'])
                    ->setRows(array_map(
                        static fn (array $row): array => [
                            $row['documentId'],
                            $row['documentTitle'],
                            $row['accessCount'],
                        ],
                        $documentAccessStats
                    ))
                    ->render();
            }
        }

        $scheduledCampaigns = $this->campaignRepository->findScheduledToRun(new \DateTimeImmutable());

        $io->section('Scheduled campaigns ready to run');
        $io->listing(array_map(
            static fn ($campaign): string => sprintf(
                '%s (%s)',
                $campaign->getName(),
                $campaign->getScheduledAt()?->format('Y-m-d H:i:s') ?? 'no date'
            ),
            $scheduledCampaigns
        ));

        return Command::SUCCESS;
    }
}