# API Documentation

## Overview

CussUp provides a comprehensive API for the support system, built with Laravel and designed for multi-tenant architecture. The API follows RESTful conventions and includes authentication, authorization, and tenant isolation.

## Base URL

```
Production: https://api.cussup.com
Development: http://localhost:8000
```

## Authentication

### Bearer Token Authentication

All API requests require authentication using Bearer tokens:

```http
Authorization: Bearer {your-token}
```

### Obtaining Access Tokens

#### Login
```http
POST /api/auth/login
```

**Request Body:**
```json
{
    "email": "user@example.com",
    "password": "password",
    "tenant_uuid": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Response:**
```json
{
    "access_token": "1|abc123...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "tenant_id": 1
    }
}
```

#### Register
```http
POST /api/auth/register
```

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "user@example.com",
    "password": "password",
    "password_confirmation": "password",
    "tenant_uuid": "550e8400-e29b-41d4-a716-446655440000"
}
```

#### Logout
```http
POST /api/auth/logout
```

**Headers:**
```http
Authorization: Bearer {your-token}
```

## Multi-Tenancy

### Tenant Context

All API endpoints automatically operate within the tenant context based on the authenticated user's tenant. The tenant is determined from the user's session or token.

### Tenant-Specific URLs

For direct tenant access, you can include the tenant UUID in the URL path:

```
GET /api/{tenant-uuid}/tickets
POST /api/{tenant-uuid}/tickets
```

## Core Endpoints

### Users

#### Get Current User
```http
GET /api/user
```

**Response:**
```json
{
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "email_verified_at": "2024-01-01T00:00:00.000000Z",
    "tenant_id": 1,
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
}
```

#### Update User Profile
```http
PUT /api/user
```

**Request Body:**
```json
{
    "name": "John Doe Updated",
    "email": "john.doe@example.com"
}
```

#### Change Password
```http
PUT /api/user/password
```

**Request Body:**
```json
{
    "current_password": "old-password",
    "password": "new-password",
    "password_confirmation": "new-password"
}
```

### Tenants

#### Get Current Tenant
```http
GET /api/tenant
```

**Response:**
```json
{
    "id": 1,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "name": "Acme Corporation",
    "domain": "acme.cussup.com",
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
}
```

#### Update Tenant
```http
PUT /api/tenant
```

**Request Body:**
```json
{
    "name": "Acme Corporation Updated",
    "domain": "acme-updated.cussup.com"
}
```

## Support System Endpoints (Planned)

### Tickets

#### List Tickets
```http
GET /api/tickets
```

**Query Parameters:**
- `status` - Filter by status (open, in_progress, resolved, closed)
- `priority` - Filter by priority (low, medium, high, urgent)
- `assigned_to` - Filter by assigned user ID
- `created_by` - Filter by creator user ID
- `page` - Page number for pagination
- `per_page` - Items per page (default: 15)

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "title": "Login Issue",
            "description": "Cannot log into the system",
            "status": "open",
            "priority": "high",
            "assigned_to": {
                "id": 2,
                "name": "Support Agent"
            },
            "created_by": {
                "id": 1,
                "name": "John Doe"
            },
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-01-01T00:00:00.000000Z"
        }
    ],
    "links": {
        "first": "http://localhost:8000/api/tickets?page=1",
        "last": "http://localhost:8000/api/tickets?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "per_page": 15,
        "to": 1,
        "total": 1
    }
}
```

#### Get Single Ticket
```http
GET /api/tickets/{id}
```

**Response:**
```json
{
    "id": 1,
    "title": "Login Issue",
    "description": "Cannot log into the system",
    "status": "open",
    "priority": "high",
    "assigned_to": {
        "id": 2,
        "name": "Support Agent"
    },
    "created_by": {
        "id": 1,
        "name": "John Doe"
    },
    "comments": [
        {
            "id": 1,
            "content": "I'm experiencing this issue too",
            "user": {
                "id": 1,
                "name": "John Doe"
            },
            "created_at": "2024-01-01T00:00:00.000000Z"
        }
    ],
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
}
```

#### Create Ticket
```http
POST /api/tickets
```

**Request Body:**
```json
{
    "title": "New Support Request",
    "description": "Detailed description of the issue",
    "priority": "medium",
    "assigned_to": 2
}
```

**Response:**
```json
{
    "id": 2,
    "title": "New Support Request",
    "description": "Detailed description of the issue",
    "status": "open",
    "priority": "medium",
    "assigned_to": {
        "id": 2,
        "name": "Support Agent"
    },
    "created_by": {
        "id": 1,
        "name": "John Doe"
    },
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
}
```

#### Update Ticket
```http
PUT /api/tickets/{id}
```

**Request Body:**
```json
{
    "title": "Updated Title",
    "description": "Updated description",
    "status": "in_progress",
    "priority": "high",
    "assigned_to": 3
}
```

#### Delete Ticket
```http
DELETE /api/tickets/{id}
```

**Response:**
```json
{
    "message": "Ticket deleted successfully"
}
```

### Ticket Comments

#### List Comments
```http
GET /api/tickets/{ticket_id}/comments
```

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "content": "I'm experiencing this issue too",
            "user": {
                "id": 1,
                "name": "John Doe"
            },
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-01-01T00:00:00.000000Z"
        }
    ]
}
```

#### Create Comment
```http
POST /api/tickets/{ticket_id}/comments
```

**Request Body:**
```json
{
    "content": "This is a new comment"
}
```

**Response:**
```json
{
    "id": 2,
    "content": "This is a new comment",
    "user": {
        "id": 1,
        "name": "John Doe"
    },
    "created_at": "2024-01-01T00:00:00.000000Z",
    "updated_at": "2024-01-01T00:00:00.000000Z"
}
```

### Ticket Attachments

#### Upload Attachment
```http
POST /api/tickets/{ticket_id}/attachments
```

**Request Body (multipart/form-data):**
```
file: [binary file data]
description: "Screenshot of the issue"
```

**Response:**
```json
{
    "id": 1,
    "filename": "screenshot.png",
    "original_name": "screenshot.png",
    "mime_type": "image/png",
    "size": 1024,
    "description": "Screenshot of the issue",
    "url": "https://storage.cussup.com/attachments/screenshot.png",
    "created_at": "2024-01-01T00:00:00.000000Z"
}
```

#### Download Attachment
```http
GET /api/attachments/{id}/download
```

## Error Handling

### Error Response Format

All API errors follow a consistent format:

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": [
            "The email field is required."
        ],
        "password": [
            "The password field is required."
        ]
    }
}
```

### HTTP Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Internal Server Error

### Common Error Codes

#### Authentication Errors
```json
{
    "message": "Unauthenticated."
}
```

#### Validation Errors
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "field_name": [
            "The field name field is required."
        ]
    }
}
```

#### Tenant Not Found
```json
{
    "message": "Tenant not found.",
    "error_code": "TENANT_NOT_FOUND"
}
```

## Rate Limiting

API requests are rate-limited to prevent abuse:

- **Authentication endpoints**: 5 requests per minute
- **General endpoints**: 60 requests per minute
- **File uploads**: 10 requests per minute

Rate limit headers are included in responses:

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1640995200
```

## Pagination

List endpoints support pagination with the following parameters:

- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 15, max: 100)

Pagination metadata is included in responses:

```json
{
    "data": [...],
    "links": {
        "first": "http://localhost:8000/api/tickets?page=1",
        "last": "http://localhost:8000/api/tickets?page=10",
        "prev": null,
        "next": "http://localhost:8000/api/tickets?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 10,
        "per_page": 15,
        "to": 15,
        "total": 150
    }
}
```

## Filtering and Sorting

### Filtering

Most list endpoints support filtering:

```http
GET /api/tickets?status=open&priority=high&assigned_to=1
```

### Sorting

Sorting is supported with the `sort` parameter:

```http
GET /api/tickets?sort=created_at,desc
GET /api/tickets?sort=title,asc
```

Multiple sort fields are supported:

```http
GET /api/tickets?sort=status,asc&sort=created_at,desc
```

## Webhooks (Planned)

### Webhook Events

The API will support webhooks for real-time notifications:

#### Available Events
- `ticket.created`
- `ticket.updated`
- `ticket.status_changed`
- `comment.created`
- `user.created`
- `user.updated`

#### Webhook Configuration
```http
POST /api/webhooks
```

**Request Body:**
```json
{
    "url": "https://your-app.com/webhooks",
    "events": ["ticket.created", "ticket.updated"],
    "secret": "webhook-secret-key"
}
```

#### Webhook Payload Example
```json
{
    "event": "ticket.created",
    "data": {
        "id": 1,
        "title": "New Support Request",
        "status": "open",
        "created_at": "2024-01-01T00:00:00.000000Z"
    },
    "timestamp": "2024-01-01T00:00:00.000000Z"
}
```

## SDKs and Libraries

### PHP SDK (Planned)
```php
use CussUp\Api\Client;

$client = new Client('your-api-token');
$tickets = $client->tickets()->list(['status' => 'open']);
```

### JavaScript SDK (Planned)
```javascript
import { CussUpClient } from '@cussup/api';

const client = new CussUpClient('your-api-token');
const tickets = await client.tickets.list({ status: 'open' });
```

## API Versioning

The API uses URL versioning:

```
/api/v1/tickets
/api/v2/tickets
```

Current version: `v1`

## Changelog

### v1.0.0 (Planned)
- Initial API release
- Authentication endpoints
- User management
- Tenant management
- Basic ticket system

### Future Versions
- Advanced ticket features
- File attachments
- Webhooks
- Real-time notifications
- Advanced reporting

---

This API documentation provides comprehensive information for integrating with the CussUp support system. For additional support, please refer to the development documentation or contact the development team. 