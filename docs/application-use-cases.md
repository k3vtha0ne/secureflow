# Application use cases

SecureFlow uses a lightweight application layer to keep business use cases explicit and testable.

## Goal

The goal is not to introduce a heavy architecture, but to separate:

- write use cases, represented by Commands;
- read use cases, represented by Queries;
- reusable business rules, kept in domain services.

## Commands

Commands describe an intention that changes the state of the system.

Example:

- `CreateCampaignCommand`
- `CreateCampaignHandler`

The command carries the input data required to create a campaign.  
The handler orchestrates the use case:

1. resolve the creator organization;
2. create the campaign entity;
3. check document access rules;
4. attach allowed documents;
5. apply scheduling rules;
6. persist the campaign.

Business rules are not duplicated in the handler. They are delegated to dedicated services such as:

- `DocumentAccessService`;
- `CampaignSchedulingService`.

## Queries

Queries describe a read-only use case.

Example:

- `CampaignStatsQuery`
- `CampaignStatsHandler`
- `CampaignStats`

The query requests campaign statistics for an organization.  
The handler reads aggregated data from the repository and returns a dedicated read model.

Queries must not persist or mutate entities.

## Why no command bus?

A command bus or asynchronous processing could be added later, but it would be unnecessary at this stage.

The current structure is intentionally simple:

- explicit use case classes;
- easy unit testing;
- no hidden magic;
- no premature complexity.

## Interview explanation

CQRS means separating write use cases from read use cases.

In SecureFlow, Commands handle actions such as creating a campaign, while Queries handle read-only needs such as computing campaign statistics.

This separation improves readability and testability without introducing a heavy architecture.
