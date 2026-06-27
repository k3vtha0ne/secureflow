# Architecture

SecureFlow is structured as a Symfony API with API Platform resources, Doctrine entities, business services and a lightweight application layer.

The goal is to keep the project readable while still showing clear separation of responsibilities.

## Main layers

```text
src/
├── Entity
├── Repository
├── Service
├── Application
├── Security
├── ApiPlatform
└── Controller
```

## Entities

The core domain model is built around:

* `Organization`
* `User`
* `Document`
* `Campaign`
* `AccessLog`

`Organization` is the tenant root. Users, documents and campaigns belong to an organization.

In simple terms, the same application can serve multiple organizations, but each user must only see data from their own organization.

## API resources

`Document` and `Campaign` are exposed as API Platform resources for secured read operations.

Write operations are intentionally not exposed directly on these resources yet. This keeps ownership and organization assignment server-side, instead of trusting client input.

The exposed resources support:

* item reads;
* collection reads;
* pagination;
* selected filters;
* explicit sorting;
* serializer groups to avoid exposing internal fields.

## Application layer

The `src/Application` directory contains explicit use cases.

Current example:

```text
src/Application/Campaign
├── Command
└── Query
```

Commands represent actions that change state. Queries represent read-only use cases.

This is not a heavy CQRS setup. There is no command bus. The goal is simply to make use cases easy to read and test.

## Business services

Reusable business rules live in `src/Service`.

Examples:

* `DocumentAccessService`
* `CampaignAccessService`
* `DocumentLifecycleService`
* `CampaignSchedulingService`

These services keep business decisions out of controllers, API configuration and voters.

## Security layer

Symfony voters are used for object-level permission checks:

* `DocumentVoter`
* `CampaignVoter`

The voters delegate business access decisions to dedicated services instead of duplicating the logic.

## API Platform Doctrine extension

`OrganizationScopeExtension` applies organization scoping at Doctrine query level.

This means collection and item queries for tenant-owned resources are automatically restricted to the current user's organization.

For cross-organization item reads, the resource is filtered out at query level and behaves like a missing resource.

## Controllers

Controllers are kept minimal.

Current controllers include:

* `HealthController` for a simple health endpoint;
* `DashboardController` for the React dashboard entry point.

The main API surface is handled through API Platform resources rather than custom controllers.
