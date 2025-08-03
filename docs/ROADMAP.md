# Development Roadmap

This document outlines the features and components that still need to be implemented for the CussUp multi-tenant support system.

## Phase 1: Core Multi-Tenancy Foundation

### 1.1 User-Tenant Relationships

**Priority: Critical**

#### Database Schema
```sql
-- Users table (already exists, needs tenant_id column)
ALTER TABLE users ADD COLUMN tenant_id BIGINT UNSIGNED;
ALTER TABLE users ADD CONSTRAINT fk_users_tenant_id FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE;

-- User-Tenant pivot table for many-to-many relationships
CREATE TABLE user_tenant (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    tenant_id BIGINT UNSIGNED NOT NULL,
    role ENUM('admin', 'agent', 'viewer') NOT NULL DEFAULT 'viewer',
    permissions JSON,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY unique_user_tenant (user_id, tenant_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

#### Models
- **User Model Updates**: Add tenant scoping and relationships
- **UserTenant Model**: New pivot model for user-tenant relationships
- **Tenant Model Updates**: Add user relationships

#### Features
- [ ] User registration with tenant association
- [ ] User invitation system
- [ ] Role-based access control (RBAC)
- [ ] User management within tenants
- [ ] Cross-tenant user management (for system admins)

### 1.2 Tenant Management

**Priority: Critical**

#### Features
- [ ] Tenant creation wizard
- [ ] Tenant settings management
- [ ] Tenant branding (logos, colors, custom domains)
- [ ] Tenant subscription/billing integration
- [ ] Tenant data export/import
- [ ] Tenant analytics dashboard

## Phase 2: Support System Core

### 2.1 Ticket System

**Priority: High**

#### Database Schema
```sql
-- Tickets table
CREATE TABLE tickets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    contact_id BIGINT UNSIGNED,
    company_id BIGINT UNSIGNED,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status_id BIGINT UNSIGNED NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    category_id BIGINT UNSIGNED,
    assigned_to BIGINT UNSIGNED,
    due_date TIMESTAMP NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
    FOREIGN KEY (status_id) REFERENCES ticket_statuses(id) ON DELETE RESTRICT,
    FOREIGN KEY (category_id) REFERENCES ticket_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_tenant_status (tenant_id, status_id),
    INDEX idx_tenant_priority (tenant_id, priority),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_due_date (due_date)
);

-- Ticket comments
CREATE TABLE ticket_comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_ticket_id (ticket_id)
);

-- Ticket attachments
CREATE TABLE ticket_attachments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    comment_id BIGINT UNSIGNED,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (comment_id) REFERENCES ticket_comments(id) ON DELETE CASCADE
);
```

#### Models
- **Ticket Model**: Core ticket entity
- **TicketComment Model**: Comments on tickets
- **TicketAttachment Model**: File attachments

#### Features
- [ ] Ticket creation and management
- [ ] Ticket assignment and reassignment
- [ ] Ticket commenting system
- [ ] File attachment support
- [ ] Ticket templates
- [ ] Ticket merging
- [ ] Ticket escalation rules
- [ ] Ticket time tracking
- [ ] Ticket history/audit trail

### 2.2 Ticket Status System

**Priority: High**

#### Database Schema
```sql
-- Ticket statuses (customizable per tenant)
CREATE TABLE ticket_statuses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(7) DEFAULT '#6B7280',
    is_default BOOLEAN DEFAULT FALSE,
    is_resolved BOOLEAN DEFAULT FALSE,
    is_closed BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tenant_status_name (tenant_id, name)
);

-- Default statuses for new tenants
CREATE TABLE default_ticket_statuses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(7) DEFAULT '#6B7280',
    is_default BOOLEAN DEFAULT FALSE,
    is_resolved BOOLEAN DEFAULT FALSE,
    is_closed BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0
);
```

#### Features
- [ ] Custom status creation per tenant
- [ ] Status workflow management
- [ ] Status-based automation rules
- [ ] Status change notifications
- [ ] Status-based reporting
- [ ] Bulk status updates

### 2.3 Custom Ticket Properties

**Priority: Medium**

#### Database Schema
```sql
-- Custom ticket properties
CREATE TABLE ticket_properties (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('text', 'number', 'email', 'url', 'date', 'select', 'multiselect', 'boolean') NOT NULL,
    options JSON, -- For select/multiselect types
    is_required BOOLEAN DEFAULT FALSE,
    is_visible BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tenant_property_name (tenant_id, name)
);

-- Ticket property values
CREATE TABLE ticket_property_values (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    property_id BIGINT UNSIGNED NOT NULL,
    value TEXT,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES ticket_properties(id) ON DELETE CASCADE,
    UNIQUE KEY unique_ticket_property (ticket_id, property_id)
);
```

#### Features
- [ ] Custom field creation and management
- [ ] Field validation rules
- [ ] Field-based filtering and reporting
- [ ] Field templates
- [ ] Bulk field updates

## Phase 3: Contact Management

### 3.1 Contacts System

**Priority: High**

#### Database Schema
```sql
-- Contacts table
CREATE TABLE contacts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(50),
    title VARCHAR(100),
    department VARCHAR(100),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100),
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
    INDEX idx_tenant_email (tenant_id, email),
    INDEX idx_company_id (company_id)
);

-- Contact custom properties (similar to ticket properties)
CREATE TABLE contact_properties (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('text', 'number', 'email', 'url', 'date', 'select', 'multiselect', 'boolean') NOT NULL,
    options JSON,
    is_required BOOLEAN DEFAULT FALSE,
    is_visible BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tenant_contact_property_name (tenant_id, name)
);

CREATE TABLE contact_property_values (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contact_id BIGINT UNSIGNED NOT NULL,
    property_id BIGINT UNSIGNED NOT NULL,
    value TEXT,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES contact_properties(id) ON DELETE CASCADE,
    UNIQUE KEY unique_contact_property (contact_id, property_id)
);
```

#### Features
- [ ] Contact creation and management
- [ ] Contact import/export (CSV, Excel)
- [ ] Contact search and filtering
- [ ] Contact activity history
- [ ] Contact notes and tags
- [ ] Contact merge functionality
- [ ] Contact deduplication
- [ ] Contact validation rules

### 3.2 Companies System

**Priority: High**

#### Database Schema
```sql
-- Companies table
CREATE TABLE companies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    website VARCHAR(255),
    phone VARCHAR(50),
    email VARCHAR(255),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100),
    industry VARCHAR(100),
    size ENUM('1-10', '11-50', '51-200', '201-500', '501-1000', '1000+'),
    annual_revenue DECIMAL(15,2),
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant_name (tenant_id, name)
);

-- Company custom properties
CREATE TABLE company_properties (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    type ENUM('text', 'number', 'email', 'url', 'date', 'select', 'multiselect', 'boolean') NOT NULL,
    options JSON,
    is_required BOOLEAN DEFAULT FALSE,
    is_visible BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tenant_company_property_name (tenant_id, name)
);

CREATE TABLE company_property_values (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    property_id BIGINT UNSIGNED NOT NULL,
    value TEXT,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES company_properties(id) ON DELETE CASCADE,
    UNIQUE KEY unique_company_property (company_id, property_id)
);
```

#### Features
- [ ] Company creation and management
- [ ] Company hierarchy support
- [ ] Company contact management
- [ ] Company activity tracking
- [ ] Company reporting and analytics
- [ ] Company import/export
- [ ] Company merge functionality

## Phase 4: Advanced Features

### 4.1 Automation & Workflows

**Priority: Medium**

#### Features
- [ ] Ticket automation rules
- [ ] Email automation
- [ ] SLA management
- [ ] Escalation workflows
- [ ] Auto-assignment rules
- [ ] Conditional logic builder

### 4.2 Reporting & Analytics

**Priority: Medium**

#### Features
- [ ] Ticket analytics dashboard
- [ ] Agent performance metrics
- [ ] Customer satisfaction surveys
- [ ] Response time tracking
- [ ] Custom report builder
- [ ] Data export capabilities

### 4.3 Integration & API

**Priority: Low**

#### Features
- [ ] Webhook system
- [ ] Third-party integrations (Slack, Teams, etc.)
- [ ] Email integration
- [ ] Calendar integration
- [ ] SSO integration
- [ ] API rate limiting and monitoring

## Implementation Timeline

### Week 1-2: Foundation
- [ ] User-tenant relationship implementation
- [ ] Basic tenant management
- [ ] Database migrations for core tables

### Week 3-4: Ticket System
- [ ] Ticket CRUD operations
- [ ] Ticket status system
- [ ] Basic ticket workflow

### Week 5-6: Contact Management
- [ ] Contact CRUD operations
- [ ] Company management
- [ ] Contact-company relationships

### Week 7-8: Advanced Features
- [ ] Custom properties system
- [ ] File attachments
- [ ] Basic reporting

### Week 9-10: Polish & Testing
- [ ] UI/UX improvements
- [ ] Comprehensive testing
- [ ] Performance optimization

## Technical Considerations

### Security
- [ ] Tenant data isolation
- [ ] User permission system
- [ ] API authentication
- [ ] File upload security
- [ ] SQL injection prevention

### Performance
- [ ] Database indexing strategy
- [ ] Query optimization
- [ ] Caching implementation
- [ ] File storage optimization
- [ ] Pagination for large datasets

### Scalability
- [ ] Horizontal scaling preparation
- [ ] Queue system for background jobs
- [ ] CDN integration for file storage
- [ ] Database partitioning strategy

## Testing Strategy

### Unit Tests
- [ ] Model relationships
- [ ] Business logic validation
- [ ] Custom property system
- [ ] Tenant isolation

### Feature Tests
- [ ] Ticket workflow
- [ ] Contact management
- [ ] User permissions
- [ ] Multi-tenant operations

### Integration Tests
- [ ] API endpoints
- [ ] File uploads
- [ ] Email notifications
- [ ] Background jobs

## Documentation Requirements

### Technical Documentation
- [ ] API documentation
- [ ] Database schema documentation
- [ ] Deployment guides
- [ ] Configuration guides

### User Documentation
- [ ] User manual
- [ ] Admin guide
- [ ] Video tutorials
- [ ] FAQ section

## Success Metrics

### Development Metrics
- [ ] Code coverage > 90%
- [ ] Zero critical security vulnerabilities
- [ ] Performance benchmarks met
- [ ] Accessibility compliance

### Business Metrics
- [ ] User adoption rate
- [ ] Ticket resolution time
- [ ] Customer satisfaction scores
- [ ] System uptime > 99.9%

---

This roadmap provides a comprehensive guide for implementing the remaining features of the CussUp multi-tenant support system. Each phase builds upon the previous one, ensuring a solid foundation for the entire system. 