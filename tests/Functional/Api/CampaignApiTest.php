<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use Symfony\Component\HttpFoundation\Response;

final class CampaignApiTest extends ApiTestCase
{
    public function testCampaignsCollectionIsScopedByUserOrganization(): void
    {
        $client = static::createClient();

        $uniqueSuffix = bin2hex(random_bytes(6));

        $alphaOrganization = $this->createOrganization(sprintf('Alpha Campaign Organization %s', $uniqueSuffix));
        $betaOrganization = $this->createOrganization(sprintf('Beta Campaign Organization %s', $uniqueSuffix));

        $alphaUser = $this->createUser(
            $alphaOrganization,
            sprintf('alpha-campaign-%s@example.test', $uniqueSuffix),
            ['ROLE_ADMIN']
        );

        $betaUser = $this->createUser(
            $betaOrganization,
            sprintf('beta-campaign-%s@example.test', $uniqueSuffix),
            ['ROLE_USER']
        );

        $alphaCampaign = $this->createCampaign(
            $alphaOrganization,
            $alphaUser,
            sprintf('Alpha Campaign %s', $uniqueSuffix)
        );

        $betaCampaign = $this->createCampaign(
            $betaOrganization,
            $betaUser,
            sprintf('Beta Campaign %s', $uniqueSuffix)
        );

        $this->flush();

        $alphaToken = $this->getJwtToken($client, $alphaUser->getUserIdentifier());

        $client->request(
            'GET',
            '/api/campaigns',
            server: $this->bearerHeaders($alphaToken)
        );

        self::assertResponseIsSuccessful();

        $payload = $this->decodeJsonResponse($client);
        $campaigns = $payload['member'] ?? $payload['hydra:member'] ?? [];
        $names = array_column($campaigns, 'name');

        self::assertContains($alphaCampaign->getName(), $names);
        self::assertNotContains($betaCampaign->getName(), $names);
    }

    public function testCampaignItemAccessIsScopedByUserOrganization(): void
    {
        $client = static::createClient();

        $uniqueSuffix = bin2hex(random_bytes(6));

        $alphaOrganization = $this->createOrganization(sprintf('Alpha Campaign Item Organization %s', $uniqueSuffix));
        $betaOrganization = $this->createOrganization(sprintf('Beta Campaign Item Organization %s', $uniqueSuffix));

        $alphaUser = $this->createUser(
            $alphaOrganization,
            sprintf('alpha-campaign-item-%s@example.test', $uniqueSuffix),
            ['ROLE_ADMIN']
        );

        $betaUser = $this->createUser(
            $betaOrganization,
            sprintf('beta-campaign-item-%s@example.test', $uniqueSuffix),
            ['ROLE_USER']
        );

        $alphaCampaign = $this->createCampaign(
            $alphaOrganization,
            $alphaUser,
            sprintf('Alpha Item Campaign %s', $uniqueSuffix)
        );

        $betaCampaign = $this->createCampaign(
            $betaOrganization,
            $betaUser,
            sprintf('Beta Item Campaign %s', $uniqueSuffix)
        );

        $this->flush();

        $alphaToken = $this->getJwtToken($client, $alphaUser->getUserIdentifier());
        $betaToken = $this->getJwtToken($client, $betaUser->getUserIdentifier());

        $client->request(
            'GET',
            sprintf('/api/campaigns/%d', $alphaCampaign->getId()),
            server: $this->bearerHeaders($alphaToken)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->request(
            'GET',
            sprintf('/api/campaigns/%d', $betaCampaign->getId()),
            server: $this->bearerHeaders($betaToken)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_OK);

        $client->request(
            'GET',
            sprintf('/api/campaigns/%d', $betaCampaign->getId()),
            server: $this->bearerHeaders($alphaToken)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $client->request(
            'GET',
            sprintf('/api/campaigns/%d', $alphaCampaign->getId()),
            server: $this->bearerHeaders($betaToken)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCampaignCreationIsNotExposedThroughApi(): void
    {
        $client = static::createClient();

        $uniqueSuffix = bin2hex(random_bytes(6));

        $organization = $this->createOrganization(sprintf('Read Only Campaign Organization %s', $uniqueSuffix));

        $user = $this->createUser(
            $organization,
            sprintf('readonly-campaign-%s@example.test', $uniqueSuffix),
            ['ROLE_ADMIN']
        );

        $this->flush();

        $token = $this->getJwtToken($client, $user->getUserIdentifier());

        $client->request(
            'POST',
            '/api/campaigns',
            server: $this->bearerHeaders($token, [
                'CONTENT_TYPE' => 'application/json',
            ]),
            content: json_encode([
                'name' => 'Client-created campaign',
                'status' => 'scheduled',
                'organization' => '/api/organizations/1',
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }
}
