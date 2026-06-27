# Security

SecureFlow uses JWT authentication and organization-based access control to protect API resources.

The security model is intentionally defensive: access is checked at several levels instead of relying on a single mechanism.

## Authentication

API authentication uses LexikJWTAuthenticationBundle.

Users authenticate with email and password through:

```text
/api/login_check
```

A successful login returns a JWT token.

Protected API requests must then include:

```text
Authorization: Bearer <token>
```

The API firewall is stateless. The server does not rely on PHP sessions for API authentication.

## Access control

The security configuration keeps the login endpoint public and protects all other API routes:

```text
/api/login_check -> public
/api/*           -> authenticated users only
```

Every authenticated user has at least `ROLE_USER`.

`ROLE_ADMIN` is treated as an organization-level role, not as a platform-wide super admin role.

## Multi-tenant isolation

SecureFlow is multi-tenant by organization.

In simple terms:

```text
one organization = one isolated workspace
```

Users, documents and campaigns belong to an organization.

A user from organization Alpha must not be able to read documents or campaigns from organization Beta.

## Query-level protection

`OrganizationScopeExtension` applies tenant filtering directly to Doctrine queries for:

* `Document`
* `Campaign`

For collections, it only returns resources owned by the current user's organization.

For item reads, cross-organization resources are filtered out at query level.

This is a strong default because it prevents accidental data exposure before the application even reaches the serialization layer.

## Object-level protection

Symfony voters provide a second security layer:

* `DocumentVoter`
* `CampaignVoter`

They are used when an entity has already been loaded and an explicit permission decision is needed.

The voters delegate the actual business rule to:

* `DocumentAccessService`
* `CampaignAccessService`

This keeps authorization logic reusable and testable.

## Defensive fallback

If no authenticated `User` is available, or if the user has no organization, the organization scope extension adds an impossible condition to the query.

This prevents accidental exposure if security configuration changes later.

## Write-side protection

Direct write operations are intentionally not exposed on `Document` and `Campaign` API resources yet.

This avoids trusting client-provided ownership or organization fields.

Write use cases should assign sensitive fields server-side, using the authenticated user as the source of truth.
