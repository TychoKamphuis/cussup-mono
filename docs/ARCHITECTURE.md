# Architecture Documentation

## Overview

CussUp is built as a single-database multi-tenant support system using Laravel 12, React 19, and Inertia.js. This document outlines the architectural decisions, patterns, and implementation details.

## Multi-Tenancy Architecture

### Single Database Approach

We chose a single-database multi-tenancy approach for the following reasons:

- **Simplified Operations**: Single database to manage, backup, and maintain
- **Cost Effective**: Reduced infrastructure costs
- **Easier Analytics**: Cross-tenant reporting and analytics
- **Simplified Migrations**: Single migration process for all tenants

### Tenant Identification Strategy

#### UUID-Based Routing
Each tenant is identified by a unique UUID in the URL path:

```
https://app.cussup.com/{tenant-uuid}/dashboard
https://app.cussup.com/{tenant-uuid}/tickets
https://app.cussup.com/{tenant-uuid}/settings
```

#### Tenant Finder Implementation

The `UuidTenantFinder` class handles tenant resolution:

```php
<?php

namespace App\Http\Utils;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class UuidTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        $segments = $request->segments();
        $uuid = $segments[0] ?? null;

        // Validate UUID format
        if (!$uuid || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid)) {
            return null;
        }

        return app(IsTenant::class)::whereUuid($uuid)->first();
    }
}
```

### Data Isolation

#### Tenant Scoping
All tenant-specific data is automatically scoped using Laravel's global scopes:

```php
// Example: User model with tenant scoping
class User extends Authenticatable
{
    protected static function booted()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $builder->where('tenant_id', tenant()->id);
        });
    }
}
```

#### Queue Awareness
Background jobs are automatically tenant-aware:

```php
// Jobs automatically inherit tenant context
class ProcessTicketJob implements ShouldQueue
{
    public function handle()
    {
        // Automatically runs in tenant context
        $tickets = Ticket::where('status', 'pending')->get();
    }
}
```

## Frontend Architecture

### Inertia.js Integration

Inertia.js provides a seamless SPA experience without the complexity of building APIs:

#### Page Structure
```
resources/js/pages/
├── auth/           # Authentication pages
├── dashboard.tsx   # Main dashboard
├── settings/       # Settings pages
└── welcome.tsx     # Landing page
```

#### Component Architecture

```
resources/js/components/
├── ui/             # Reusable UI components (Radix-based)
├── app-*.tsx       # App-specific components
├── nav-*.tsx       # Navigation components
└── layouts/        # Page layout components
```

### State Management

#### Server State
- Managed by Laravel backend
- Inertia.js handles data passing
- No client-side state management needed for most cases

#### Client State
- React hooks for UI state
- Local storage for user preferences
- Theme switching and navigation state

### TypeScript Integration

Full TypeScript support with proper type definitions:

```typescript
// resources/js/types/index.d.ts
export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
}

export interface Tenant {
    id: number;
    uuid: string;
    name: string;
    domain?: string;
}
```

## Database Design

### Core Tables

#### Tenants Table
```sql
CREATE TABLE tenants (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uuid CHAR(36) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

#### Users Table
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### Data Relationships

#### Tenant-User Relationship
- One-to-Many: Tenant → Users
- Users belong to a single tenant
- Global scope ensures data isolation

#### Future Support System Tables
```sql
-- Tickets table (planned)
CREATE TABLE tickets (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('open', 'in_progress', 'resolved', 'closed'),
    priority ENUM('low', 'medium', 'high', 'urgent'),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Ticket comments table (planned)
CREATE TABLE ticket_comments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    ticket_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (ticket_id) REFERENCES tickets(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

## Security Considerations

### Tenant Isolation
- **Database Level**: All queries scoped by tenant_id
- **Application Level**: Middleware ensures tenant context
- **Queue Level**: Jobs automatically tenant-aware

### Authentication & Authorization
- Users can only access their tenant's data
- Session management per tenant
- Role-based permissions (planned)

### Data Validation
- Form requests validate tenant context
- Input sanitization and validation
- CSRF protection via Inertia.js

## Performance Considerations

### Database Optimization
- Proper indexing on tenant_id columns
- Query optimization with eager loading
- Database connection pooling

### Frontend Performance
- Vite for fast development and optimized builds
- Code splitting and lazy loading
- Optimized bundle sizes

### Caching Strategy
- Route caching for production
- Configuration caching
- View caching
- Tenant-specific cache prefixes

## Scalability

### Horizontal Scaling
- Stateless application design
- Session storage in Redis/database
- Load balancer ready

### Database Scaling
- Read replicas for reporting
- Connection pooling
- Query optimization

### Future Considerations
- Microservices architecture (if needed)
- API-first approach for mobile apps
- Webhook system for integrations

## Development Workflow

### Local Development
```bash
# Start all services
composer run dev

# Individual services
php artisan serve          # Laravel development server
npm run dev               # Vite development server
php artisan queue:work    # Queue worker
```

### Testing Strategy
- **Feature Tests**: End-to-end functionality
- **Unit Tests**: Individual component testing
- **Browser Tests**: User interaction testing (planned)

### Deployment Pipeline
1. Code review and testing
2. Staging environment deployment
3. Production deployment with zero-downtime
4. Database migrations
5. Cache warming

## Monitoring & Observability

### Application Monitoring
- Laravel Telescope for debugging
- Error tracking and logging
- Performance monitoring

### Infrastructure Monitoring
- Server resource monitoring
- Database performance metrics
- Queue monitoring

### Business Metrics
- Tenant usage analytics
- Support ticket metrics
- User engagement tracking

## Future Enhancements

### Planned Features
- **API Development**: RESTful API for mobile apps
- **Webhook System**: Third-party integrations
- **Advanced Permissions**: Role-based access control
- **Reporting Dashboard**: Analytics and insights
- **Multi-language Support**: Internationalization
- **Mobile App**: React Native companion app

### Technical Improvements
- **GraphQL API**: For complex data queries
- **Real-time Features**: WebSocket integration
- **Advanced Caching**: Redis for session and cache
- **Search Functionality**: Full-text search with Elasticsearch
- **File Storage**: Cloud storage integration

---

This architecture provides a solid foundation for a scalable, maintainable multi-tenant support system while keeping the development experience smooth and efficient. 