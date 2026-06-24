# Read API Resources

## Purpose

This document describes the first read-only API resources exposed by SecureFlow through API Platform.

At this stage, the API focuses on controlled read access for the main business resources:

* documents
* campaigns

Write operations are intentionally not exposed yet. Creation, update and deletion will require authentication, organization scoping, access-control checks and audit logging.

## API design principles

The API follows these principles:

* expose only useful read fields;
* avoid exposing internal relations too early;
* avoid exposing storage paths or technical fields;
* keep write operations disabled until security is implemented;
* define explicit filters instead of exposing arbitrary query capabilities;
* prepare the API for future authentication and multi-tenant isolation.

## Document resource

### Endpoint

```txt
GET /api/documents
GET /api/documents/{id}
```

### Serialization group

```txt
document:read
```

### Exposed fields

The `Document` API exposes only the fields needed for a read-only document listing or detail view:

```txt
id
title
description
status
createdAt
updatedAt
```

The following fields are intentionally not exposed at this stage:

```txt
storagePath
owner
organization
isDeleted
campaigns
```

### Reasoning

`storagePath` is internal and should not be exposed directly through the API.

`owner` and `organization` are related to access control and multi-tenant isolation. They will be handled later through authentication, voters, custom providers or dedicated DTOs.

`isDeleted` is an internal soft-delete flag. Deleted documents should be filtered server-side rather than exposed as a public API concern.

### Available filters

```txt
GET /api/documents?status=published
GET /api/documents?search[title]=security
GET /api/documents?search[description]=policy
```

When needed, the encoded form can be used:

```txt
GET /api/documents?search%5Btitle%5D=security
```

### Available sorting parameters

```txt
GET /api/documents?sortCreatedAt=desc
GET /api/documents?sortTitle=asc
GET /api/documents?sortStatus=asc
```

## Campaign resource

### Endpoint

```txt
GET /api/campaigns
GET /api/campaigns/{id}
```

### Serialization group

```txt
campaign:read
```

### Exposed fields

The `Campaign` API exposes only the fields needed for a read-only campaign listing or detail view:

```txt
id
name
description
status
scheduledAt
createdAt
updatedAt
```

The following fields are intentionally not exposed at this stage:

```txt
organization
createdBy
documents
```

### Reasoning

`organization` and `createdBy` are access-control concerns. They should not be exposed before authentication and multi-tenant filtering are implemented.

`documents` is a relation that can introduce nested serialization, larger payloads and possible access-control issues. It will be exposed later through a controlled representation if needed.

### Available filters

```txt
GET /api/campaigns?status=scheduled
GET /api/campaigns?search[name]=alpha
GET /api/campaigns?search[description]=onboarding
```

Encoded form:

```txt
GET /api/campaigns?search%5Bname%5D=alpha
```

### Available sorting parameters

```txt
GET /api/campaigns?sortCreatedAt=desc
GET /api/campaigns?sortScheduledAt=asc
GET /api/campaigns?sortName=asc
GET /api/campaigns?sortStatus=asc
```

## Security status

Authentication is not enforced yet on these read resources.

This is a temporary development step. The current goal is to validate:

* API Platform resource exposure;
* serialization groups;
* read-only operations;
* explicit filters;
* sorting parameters;
* JSON output structure.

Security will be implemented in a dedicated step with API authentication and access-control rules.

Future security work will include:

* authenticated API access;
* user-based access control;
* organization-based filtering;
* prevention of cross-tenant data access;
* audit logging for sensitive actions.

## Validation commands

Run the following commands after changing API resources:

```bash
make validate
php bin/console debug:router | grep documents
php bin/console debug:router | grep campaigns
```

Manual API checks:

```bash
curl -i "http://127.0.0.1:8001/api/documents"
curl -i "http://127.0.0.1:8001/api/documents?status=published"
curl -i "http://127.0.0.1:8001/api/documents?search%5Btitle%5D=security"
curl -i "http://127.0.0.1:8001/api/campaigns"
curl -i "http://127.0.0.1:8001/api/campaigns?status=scheduled"
curl -i "http://127.0.0.1:8001/api/campaigns?search%5Bname%5D=alpha"
```

## Next steps

The next API iterations should focus on:

1. authentication;
2. organization-scoped data access;
3. voters or custom security rules;
4. custom providers for filtered collections;
5. write operations with server-side ownership assignment;
6. audit logging on sensitive operations.
