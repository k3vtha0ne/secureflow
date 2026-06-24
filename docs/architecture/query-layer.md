# Query Layer

## Purpose

The query layer centralizes read-oriented database access in dedicated Doctrine repositories.

The goal is to avoid spreading query logic across controllers or services, and to keep tenant isolation explicit in every business query.

## Multi-tenant filtering

SecureFlow uses a simple multi-tenant model based on `Organization`.

Most business entities are linked to an organization:

- `User`
- `Document`
- `Campaign`
- `AccessLog`

Repository methods must filter by organization whenever data can be scoped to a tenant.

This prevents accidental data leakage between organizations.

## DocumentRepository

Main responsibilities:

- list non-deleted documents by organization;
- count non-deleted documents by organization;
- list documents owned by a user.

Important filters:

- `organization`
- `owner`
- `isDeleted`
- `createdAt`

## UserRepository

Main responsibilities:

- list users by organization;
- count users by organization;
- search users by email, first name or last name within an organization.

The search method remains organization-scoped to preserve tenant isolation.

## CampaignRepository

Main responsibilities:

- list campaigns by organization;
- list campaigns created by a user;
- find scheduled campaigns ready to run;
- count campaigns by status.

The `findScheduledToRun()` method prepares future automated processing through a console command, cron job or worker.

## AccessLogRepository

Main responsibilities:

- list recent logs by organization;
- list recent logs by document;
- count accesses by document;
- count actions by organization.

Access logs intentionally do not use inverse collections on `User`, `Document` or `Organization`, because logs can grow quickly and should be queried explicitly.

## Index strategy

Indexes were added after defining the main business queries.

The goal is to support real access patterns instead of indexing randomly.

Main indexed dimensions:

- organization + created date;
- organization + soft deletion status;
- owner + soft deletion status;
- campaign status + scheduled date;
- document + log creation date;
- organization + log action.

## Validation

Useful commands:

- `make validate`
- `make report`
- `make db-status`