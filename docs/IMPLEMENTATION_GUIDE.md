# Implementation Guide

This document provides step-by-step instructions for implementing the core features of the CussUp multi-tenant support system.

## Phase 1: Database Migrations

### 1.1 Update Users Table

Create a new migration to add tenant_id to the users table:

```bash
php artisan make:migration add_tenant_id_to_users_table
```

**Migration Content:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->index(['tenant_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id', 'email']);
            $table->dropColumn('tenant_id');
        });
    }
};
```

### 1.2 Create User-Tenant Pivot Table

```bash
php artisan make:migration create_user_tenant_table
```

**Migration Content:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_tenant', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['admin', 'agent', 'viewer'])->default('viewer');
            $table->json('permissions')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'tenant_id']);
            $table->index(['tenant_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tenant');
    }
};
```

### 1.3 Create Ticket Statuses Table

```bash
php artisan make:migration create_ticket_statuses_table
```

**Migration Content:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('color', 7)->default('#6B7280');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_resolved')->default(false);
            $table->boolean('is_closed')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->unique(['tenant_id', 'name']);
            $table->index(['tenant_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_statuses');
    }
};
```

### 1.4 Create Tickets Table

```bash
php artisan make:migration create_tickets_table
```

**Migration Content:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('status_id')->constrained('ticket_statuses')->restrictOnDelete();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('due_date')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'status_id']);
            $table->index(['tenant_id', 'priority']);
            $table->index(['assigned_to']);
            $table->index(['due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
```

### 1.5 Create Ticket Comments Table

```bash
php artisan make:migration create_ticket_comments_table
```

**Migration Content:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
            
            $table->index(['ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_comments');
    }
};
```

### 1.6 Create Contacts Table

```bash
php artisan make:migration create_contacts_table
```

**Migration Content:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('email')->unique();
            $table->string('phone', 50)->nullable();
            $table->string('title', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['tenant_id', 'email']);
            $table->index(['company_id']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
```

### 1.7 Create Companies Table

```bash
php artisan make:migration create_companies_table
```

**Migration Content:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('website')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('industry', 100)->nullable();
            $table->enum('size', ['1-10', '11-50', '51-200', '201-500', '501-1000', '1000+'])->nullable();
            $table->decimal('annual_revenue', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['tenant_id', 'name']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
```

## Phase 2: Model Implementation

### 2.1 Update User Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Multitenancy\Models\Concerns\BelongsToTenant;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, BelongsToTenant;

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Tenant scoping
    protected static function booted()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    // Relationships
    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'user_tenant')
                    ->withPivot('role', 'permissions')
                    ->withTimestamps();
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class);
    }

    // Helper methods
    public function hasRole(string $role, Tenant $tenant = null): bool
    {
        $tenant = $tenant ?? tenant();
        return $this->tenants()
                    ->where('tenant_id', $tenant->id)
                    ->wherePivot('role', $role)
                    ->exists();
    }

    public function getRole(Tenant $tenant = null): ?string
    {
        $tenant = $tenant ?? tenant();
        $pivot = $this->tenants()
                      ->where('tenant_id', $tenant->id)
                      ->first()?->pivot;
        
        return $pivot?->role;
    }
}
```

### 2.2 Create Ticket Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Multitenancy\Models\Concerns\BelongsToTenant;

class Ticket extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'title',
        'description',
        'status_id',
        'priority',
        'assigned_to',
        'due_date',
        'contact_id',
        'company_id',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // Tenant scoping
    protected static function booted()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function status()
    {
        return $this->belongsTo(TicketStatus::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class);
    }

    // Helper methods
    public function isResolved(): bool
    {
        return $this->status->is_resolved || $this->resolved_at !== null;
    }

    public function isClosed(): bool
    {
        return $this->status->is_closed;
    }

    public function markAsResolved(): void
    {
        $this->update(['resolved_at' => now()]);
    }

    public function assignTo(User $user): void
    {
        $this->update(['assigned_to' => $user->id]);
    }
}
```

### 2.3 Create TicketStatus Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Multitenancy\Models\Concerns\BelongsToTenant;

class TicketStatus extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'name',
        'color',
        'is_default',
        'is_resolved',
        'is_closed',
        'sort_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_resolved' => 'boolean',
        'is_closed' => 'boolean',
    ];

    // Tenant scoping
    protected static function booted()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    // Relationships
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'status_id');
    }

    // Helper methods
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }

    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    public function scopeClosed($query)
    {
        return $query->where('is_closed', true);
    }
}
```

### 2.4 Create Contact Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Multitenancy\Models\Concerns\BelongsToTenant;

class Contact extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'title',
        'department',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'notes',
        'company_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Tenant scoping
    protected static function booted()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    // Helper methods
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCompany($query, Company $company)
    {
        return $query->where('company_id', $company->id);
    }
}
```

### 2.5 Create Company Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Multitenancy\Models\Concerns\BelongsToTenant;

class Company extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'name',
        'website',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'industry',
        'size',
        'annual_revenue',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'annual_revenue' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Tenant scoping
    protected static function booted()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    // Relationships
    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    // Helper methods
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByIndustry($query, string $industry)
    {
        return $query->where('industry', $industry);
    }
}
```

## Phase 3: Controller Implementation

### 3.1 Create Ticket Controller

```bash
php artisan make:controller TicketController --resource
```

**Controller Content:**
```php
<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\Contact;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        $tickets = Ticket::with(['status', 'assignedTo', 'contact', 'company'])
            ->when($request->search, function ($query, $search) {
                $query->where('title', 'like', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status_id', $status);
            })
            ->when($request->priority, function ($query, $priority) {
                $query->where('priority', $priority);
            })
            ->when($request->assigned_to, function ($query, $assignedTo) {
                $query->where('assigned_to', $assignedTo);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $statuses = TicketStatus::orderBy('sort_order')->get();
        $agents = User::whereHas('tenants', function ($query) {
            $query->where('tenant_id', tenant()->id)
                  ->whereIn('role', ['admin', 'agent']);
        })->get();

        return Inertia::render('Tickets/Index', [
            'tickets' => $tickets,
            'statuses' => $statuses,
            'agents' => $agents,
            'filters' => $request->only(['search', 'status', 'priority', 'assigned_to']),
        ]);
    }

    public function create(): Response
    {
        $statuses = TicketStatus::orderBy('sort_order')->get();
        $contacts = Contact::active()->get();
        $companies = Company::active()->get();
        $agents = User::whereHas('tenants', function ($query) {
            $query->where('tenant_id', tenant()->id)
                  ->whereIn('role', ['admin', 'agent']);
        })->get();

        return Inertia::render('Tickets/Create', [
            'statuses' => $statuses,
            'contacts' => $contacts,
            'companies' => $companies,
            'agents' => $agents,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status_id' => 'required|exists:ticket_statuses,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'company_id' => 'nullable|exists:companies,id',
            'due_date' => 'nullable|date',
        ]);

        $ticket = Ticket::create([
            ...$validated,
            'user_id' => auth()->id(),
            'tenant_id' => tenant()->id,
        ]);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket created successfully.');
    }

    public function show(Ticket $ticket): Response
    {
        $ticket->load(['status', 'assignedTo', 'contact', 'company', 'comments.user']);

        return Inertia::render('Tickets/Show', [
            'ticket' => $ticket,
        ]);
    }

    public function edit(Ticket $ticket): Response
    {
        $ticket->load(['status', 'assignedTo', 'contact', 'company']);
        
        $statuses = TicketStatus::orderBy('sort_order')->get();
        $contacts = Contact::active()->get();
        $companies = Company::active()->get();
        $agents = User::whereHas('tenants', function ($query) {
            $query->where('tenant_id', tenant()->id)
                  ->whereIn('role', ['admin', 'agent']);
        })->get();

        return Inertia::render('Tickets/Edit', [
            'ticket' => $ticket,
            'statuses' => $statuses,
            'contacts' => $contacts,
            'companies' => $companies,
            'agents' => $agents,
        ]);
    }

    public function update(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status_id' => 'required|exists:ticket_statuses,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'company_id' => 'nullable|exists:companies,id',
            'due_date' => 'nullable|date',
        ]);

        $ticket->update($validated);

        return redirect()->route('tickets.show', $ticket)
            ->with('success', 'Ticket updated successfully.');
    }

    public function destroy(Ticket $ticket)
    {
        $ticket->delete();

        return redirect()->route('tickets.index')
            ->with('success', 'Ticket deleted successfully.');
    }
}
```

## Phase 4: Frontend Implementation

### 4.1 Create Ticket Index Page

Create `resources/js/pages/Tickets/Index.tsx`:

```tsx
import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Ticket, TicketStatus, User } from '@/types';

interface Props {
  tickets: {
    data: Ticket[];
    links: any[];
  };
  statuses: TicketStatus[];
  agents: User[];
  filters: {
    search?: string;
    status?: string;
    priority?: string;
    assigned_to?: string;
  };
}

export default function TicketsIndex({ tickets, statuses, agents, filters }: Props) {
  const [search, setSearch] = useState(filters.search || '');
  const [status, setStatus] = useState(filters.status || '');
  const [priority, setPriority] = useState(filters.priority || '');
  const [assignedTo, setAssignedTo] = useState(filters.assigned_to || '');

  const handleFilter = () => {
    router.get(route('tickets.index'), {
      search,
      status,
      priority,
      assigned_to: assignedTo,
    }, {
      preserveState: true,
    });
  };

  const getPriorityColor = (priority: string) => {
    switch (priority) {
      case 'urgent': return 'bg-red-100 text-red-800';
      case 'high': return 'bg-orange-100 text-orange-800';
      case 'medium': return 'bg-yellow-100 text-yellow-800';
      case 'low': return 'bg-green-100 text-green-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  return (
    <>
      <Head title="Tickets" />
      
      <div className="space-y-6">
        <div className="flex justify-between items-center">
          <h1 className="text-2xl font-bold">Tickets</h1>
          <Link href={route('tickets.create')}>
            <Button>Create Ticket</Button>
          </Link>
        </div>

        {/* Filters */}
        <Card>
          <CardHeader>
            <CardTitle>Filters</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
              <Input
                placeholder="Search tickets..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
              />
              
              <Select value={status} onValueChange={setStatus}>
                <SelectTrigger>
                  <SelectValue placeholder="All Statuses" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All Statuses</SelectItem>
                  {statuses.map((status) => (
                    <SelectItem key={status.id} value={status.id.toString()}>
                      {status.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>

              <Select value={priority} onValueChange={setPriority}>
                <SelectTrigger>
                  <SelectValue placeholder="All Priorities" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All Priorities</SelectItem>
                  <SelectItem value="low">Low</SelectItem>
                  <SelectItem value="medium">Medium</SelectItem>
                  <SelectItem value="high">High</SelectItem>
                  <SelectItem value="urgent">Urgent</SelectItem>
                </SelectContent>
              </Select>

              <Select value={assignedTo} onValueChange={setAssignedTo}>
                <SelectTrigger>
                  <SelectValue placeholder="All Agents" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">All Agents</SelectItem>
                  {agents.map((agent) => (
                    <SelectItem key={agent.id} value={agent.id.toString()}>
                      {agent.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            
            <div className="mt-4">
              <Button onClick={handleFilter}>Apply Filters</Button>
            </div>
          </CardContent>
        </Card>

        {/* Tickets List */}
        <div className="space-y-4">
          {tickets.data.map((ticket) => (
            <Card key={ticket.id}>
              <CardContent className="p-6">
                <div className="flex justify-between items-start">
                  <div className="space-y-2">
                    <Link 
                      href={route('tickets.show', ticket.id)}
                      className="text-lg font-semibold hover:text-blue-600"
                    >
                      {ticket.title}
                    </Link>
                    
                    <div className="flex items-center space-x-4 text-sm text-gray-600">
                      <span>#{ticket.id}</span>
                      <Badge 
                        className="text-xs"
                        style={{ backgroundColor: ticket.status.color }}
                      >
                        {ticket.status.name}
                      </Badge>
                      <Badge className={`text-xs ${getPriorityColor(ticket.priority)}`}>
                        {ticket.priority}
                      </Badge>
                      {ticket.assigned_to && (
                        <span>Assigned to {ticket.assigned_to.name}</span>
                      )}
                    </div>
                  </div>
                  
                  <div className="text-sm text-gray-500">
                    {new Date(ticket.created_at).toLocaleDateString()}
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>

        {/* Pagination */}
        {tickets.links.length > 3 && (
          <div className="flex justify-center">
            {/* Add pagination component here */}
          </div>
        )}
      </div>
    </>
  );
}
```

### 4.2 Create Ticket Form Component

Create `resources/js/components/TicketForm.tsx`:

```tsx
import React from 'react';
import { useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Ticket, TicketStatus, Contact, Company, User } from '@/types';

interface Props {
  ticket?: Ticket;
  statuses: TicketStatus[];
  contacts: Contact[];
  companies: Company[];
  agents: User[];
}

export default function TicketForm({ ticket, statuses, contacts, companies, agents }: Props) {
  const { data, setData, post, put, processing, errors } = useForm({
    title: ticket?.title || '',
    description: ticket?.description || '',
    status_id: ticket?.status_id?.toString() || '',
    priority: ticket?.priority || 'medium',
    assigned_to: ticket?.assigned_to?.toString() || '',
    contact_id: ticket?.contact_id?.toString() || '',
    company_id: ticket?.company_id?.toString() || '',
    due_date: ticket?.due_date ? new Date(ticket.due_date).toISOString().split('T')[0] : '',
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (ticket) {
      put(route('tickets.update', ticket.id));
    } else {
      post(route('tickets.store'));
    }
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>{ticket ? 'Edit Ticket' : 'Create Ticket'}</CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <Label htmlFor="title">Title</Label>
              <Input
                id="title"
                value={data.title}
                onChange={(e) => setData('title', e.target.value)}
                error={errors.title}
              />
            </div>

            <div>
              <Label htmlFor="status_id">Status</Label>
              <Select value={data.status_id} onValueChange={(value) => setData('status_id', value)}>
                <SelectTrigger>
                  <SelectValue placeholder="Select status" />
                </SelectTrigger>
                <SelectContent>
                  {statuses.map((status) => (
                    <SelectItem key={status.id} value={status.id.toString()}>
                      {status.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label htmlFor="priority">Priority</Label>
              <Select value={data.priority} onValueChange={(value) => setData('priority', value)}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="low">Low</SelectItem>
                  <SelectItem value="medium">Medium</SelectItem>
                  <SelectItem value="high">High</SelectItem>
                  <SelectItem value="urgent">Urgent</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label htmlFor="assigned_to">Assign To</Label>
              <Select value={data.assigned_to} onValueChange={(value) => setData('assigned_to', value)}>
                <SelectTrigger>
                  <SelectValue placeholder="Select agent" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">Unassigned</SelectItem>
                  {agents.map((agent) => (
                    <SelectItem key={agent.id} value={agent.id.toString()}>
                      {agent.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label htmlFor="contact_id">Contact</Label>
              <Select value={data.contact_id} onValueChange={(value) => setData('contact_id', value)}>
                <SelectTrigger>
                  <SelectValue placeholder="Select contact" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">No Contact</SelectItem>
                  {contacts.map((contact) => (
                    <SelectItem key={contact.id} value={contact.id.toString()}>
                      {contact.full_name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label htmlFor="company_id">Company</Label>
              <Select value={data.company_id} onValueChange={(value) => setData('company_id', value)}>
                <SelectTrigger>
                  <SelectValue placeholder="Select company" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">No Company</SelectItem>
                  {companies.map((company) => (
                    <SelectItem key={company.id} value={company.id.toString()}>
                      {company.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label htmlFor="due_date">Due Date</Label>
              <Input
                id="due_date"
                type="date"
                value={data.due_date}
                onChange={(e) => setData('due_date', e.target.value)}
              />
            </div>
          </div>

          <div>
            <Label htmlFor="description">Description</Label>
            <Textarea
              id="description"
              value={data.description}
              onChange={(e) => setData('description', e.target.value)}
              rows={6}
            />
          </div>

          <div className="flex justify-end space-x-4">
            <Button type="button" variant="outline" onClick={() => window.history.back()}>
              Cancel
            </Button>
            <Button type="submit" disabled={processing}>
              {ticket ? 'Update Ticket' : 'Create Ticket'}
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  );
}
```

## Phase 5: Routes

Add the following routes to `routes/web.php`:

```php
// Ticket routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('tickets', TicketController::class);
    
    // Contact routes
    Route::resource('contacts', ContactController::class);
    
    // Company routes
    Route::resource('companies', CompanyController::class);
    
    // Ticket status routes
    Route::resource('ticket-statuses', TicketStatusController::class);
});
```

## Phase 6: Testing

### 6.1 Create Feature Tests

```bash
php artisan make:test TicketTest
```

**Test Content:**
```php
<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a tenant and set it as current
        $tenant = Tenant::factory()->create();
        $this->actingAs($tenant);
    }

    public function test_user_can_view_tickets_index()
    {
        $user = User::factory()->create(['tenant_id' => tenant()->id]);
        $this->actingAs($user);

        $response = $this->get(route('tickets.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tickets/Index'));
    }

    public function test_user_can_create_ticket()
    {
        $user = User::factory()->create(['tenant_id' => tenant()->id]);
        $status = TicketStatus::factory()->create(['tenant_id' => tenant()->id]);
        
        $this->actingAs($user);

        $ticketData = [
            'title' => 'Test Ticket',
            'description' => 'Test Description',
            'status_id' => $status->id,
            'priority' => 'medium',
        ];

        $response = $this->post(route('tickets.store'), $ticketData);

        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'title' => 'Test Ticket',
            'user_id' => $user->id,
            'tenant_id' => tenant()->id,
        ]);
    }

    public function test_ticket_is_tenant_scoped()
    {
        $user = User::factory()->create(['tenant_id' => tenant()->id]);
        $ticket = Ticket::factory()->create([
            'tenant_id' => tenant()->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('tickets.show', $ticket));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Tickets/Show')
                 ->has('ticket', fn ($ticket) => $ticket->id === $ticket->id)
        );
    }
}
```

## Next Steps

1. **Run Migrations**: Execute all database migrations
2. **Create Seeders**: Add seeders for default ticket statuses and test data
3. **Implement Remaining Controllers**: Create ContactController and CompanyController
4. **Add Frontend Pages**: Create the remaining React components
5. **Add Validation**: Implement comprehensive form validation
6. **Add Authorization**: Implement role-based access control
7. **Add Notifications**: Implement email notifications for ticket updates
8. **Add File Uploads**: Implement ticket attachment functionality
9. **Add Search**: Implement advanced search and filtering
10. **Add Reporting**: Create analytics and reporting features

This implementation guide provides a solid foundation for building the multi-tenant support system. Each component is designed with tenant isolation in mind and follows Laravel best practices. 