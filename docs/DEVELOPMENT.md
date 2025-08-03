# Development Guide

## Getting Started

### Prerequisites

Before you begin, ensure you have the following installed:

- **PHP 8.2+** with extensions: `bcmath`, `ctype`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`
- **Node.js 18+** and npm
- **Composer** (PHP package manager)
- **Git** for version control
- **MySQL 8.0+** or **PostgreSQL 13+**

### Local Development Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd cussup-mono
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database setup**
   ```bash
   # Update .env with your database credentials
   php artisan migrate
   php artisan db:seed  # If seeders are available
   ```

6. **Start development servers**
   ```bash
   # Start all services (recommended)
   composer run dev
   
   # Or start individually:
   php artisan serve --port=8000
   npm run dev
   php artisan queue:work
   ```

### Development URLs

- **Main Application**: http://localhost:8000
- **Vite Dev Server**: http://localhost:5173
- **Laravel Telescope** (if enabled): http://localhost:8000/telescope

## Project Structure

### Backend Structure

```
app/
├── Http/
│   ├── Controllers/          # Laravel controllers
│   │   ├── Auth/            # Authentication controllers
│   │   └── Settings/        # Settings controllers
│   ├── Middleware/          # Custom middleware
│   ├── Requests/            # Form request validation
│   └── Utils/
│       └── UuidTenantFinder.php  # Custom tenant finder
├── Models/                  # Eloquent models
├── Providers/              # Service providers
└── Services/               # Business logic services (planned)
```

### Frontend Structure

```
resources/js/
├── components/             # React components
│   ├── ui/                # Reusable UI components
│   ├── app-*.tsx          # App-specific components
│   └── nav-*.tsx          # Navigation components
├── hooks/                 # Custom React hooks
├── layouts/               # Page layouts
├── pages/                 # Inertia pages
├── types/                 # TypeScript definitions
└── lib/                   # Utility functions
```

## Coding Standards

### PHP/Laravel Standards

#### PSR-12 Compliance
- Follow PSR-12 coding standards
- Use 4 spaces for indentation
- Use single quotes for strings unless interpolation is needed
- Use trailing commas in arrays

#### Laravel Conventions
```php
// Controller naming
class TicketController extends Controller
{
    // Use resource methods: index, show, create, store, edit, update, destroy
    public function index()
    {
        return Inertia::render('Tickets/Index', [
            'tickets' => Ticket::paginate(15)
        ]);
    }
}

// Model conventions
class Ticket extends Model
{
    protected $fillable = ['title', 'description', 'status'];
    protected $casts = ['created_at' => 'datetime'];
    
    // Use relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

#### Multi-Tenancy Patterns
```php
// Always scope tenant data
class Ticket extends Model
{
    protected static function booted()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $builder->where('tenant_id', tenant()->id);
        });
    }
}

// Use tenant-aware jobs
class ProcessTicketJob implements ShouldQueue
{
    public function handle()
    {
        // Automatically runs in tenant context
        $tickets = Ticket::where('status', 'pending')->get();
    }
}
```

### TypeScript/React Standards

#### TypeScript Conventions
```typescript
// Use interfaces for props
interface TicketProps {
    ticket: Ticket;
    onUpdate: (id: number, data: Partial<Ticket>) => void;
}

// Use type aliases for complex types
type TicketStatus = 'open' | 'in_progress' | 'resolved' | 'closed';

// Use enums for constants
enum TicketPriority {
    LOW = 'low',
    MEDIUM = 'medium',
    HIGH = 'high',
    URGENT = 'urgent'
}
```

#### React Conventions
```typescript
// Use functional components with hooks
const TicketList: React.FC<TicketListProps> = ({ tickets, onStatusChange }) => {
    const [filter, setFilter] = useState<TicketStatus>('open');
    
    return (
        <div className="space-y-4">
            {/* Component JSX */}
        </div>
    );
};

// Use custom hooks for shared logic
const useTickets = () => {
    const [tickets, setTickets] = useState<Ticket[]>([]);
    const [loading, setLoading] = useState(false);
    
    const fetchTickets = useCallback(async () => {
        setLoading(true);
        // Fetch logic
        setLoading(false);
    }, []);
    
    return { tickets, loading, fetchTickets };
};
```

#### Component Structure
```typescript
// Component file structure
const ComponentName: React.FC<ComponentProps> = ({ prop1, prop2 }) => {
    // 1. Hooks
    const [state, setState] = useState();
    
    // 2. Event handlers
    const handleClick = () => {
        // Handler logic
    };
    
    // 3. Effects
    useEffect(() => {
        // Effect logic
    }, []);
    
    // 4. Render
    return (
        <div>
            {/* JSX */}
        </div>
    );
};

export default ComponentName;
```

## Development Workflow

### Git Workflow

#### Branch Naming
```
feature/ticket-management
bugfix/tenant-isolation
hotfix/security-patch
chore/update-dependencies
```

#### Commit Messages
```
feat: add ticket management system
fix: resolve tenant isolation issue
docs: update API documentation
test: add unit tests for ticket service
refactor: improve component structure
```

### Feature Development

#### 1. Create Feature Branch
```bash
git checkout -b feature/ticket-management
```

#### 2. Development Process
```bash
# Start development servers
composer run dev

# Run tests during development
composer test

# Check code quality
npm run lint
npm run types
```

#### 3. Testing
```bash
# Run all tests
composer test

# Run specific test
php artisan test --filter=TicketTest

# Run with coverage
php artisan test --coverage
```

#### 4. Code Review
```bash
# Format code
npm run format
composer format

# Check for issues
npm run lint
composer analyse
```

### Database Migrations

#### Creating Migrations
```bash
# Create migration
php artisan make:migration create_tickets_table

# Run migrations
php artisan migrate

# Rollback migration
php artisan migrate:rollback
```

#### Migration Best Practices
```php
// Always include tenant_id for multi-tenancy
public function up(): void
{
    Schema::create('tickets', function (Blueprint $table) {
        $table->id();
        $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('title');
        $table->text('description');
        $table->enum('status', ['open', 'in_progress', 'resolved', 'closed']);
        $table->timestamps();
        
        // Indexes for performance
        $table->index(['tenant_id', 'status']);
        $table->index(['tenant_id', 'user_id']);
    });
}
```

## Testing

### PHP Testing with Pest

#### Feature Tests
```php
// tests/Feature/TicketTest.php
test('user can create ticket', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->post('/tickets', [
            'title' => 'Test Ticket',
            'description' => 'Test Description'
        ]);
    
    $response->assertRedirect();
    $this->assertDatabaseHas('tickets', [
        'title' => 'Test Ticket',
        'user_id' => $user->id
    ]);
});
```

#### Unit Tests
```php
// tests/Unit/TicketServiceTest.php
test('ticket service creates ticket with correct data', function () {
    $service = new TicketService();
    $data = ['title' => 'Test', 'description' => 'Test'];
    
    $ticket = $service->createTicket($data);
    
    expect($ticket)->toBeInstanceOf(Ticket::class);
    expect($ticket->title)->toBe('Test');
});
```

### Frontend Testing

#### Component Testing (Planned)
```typescript
// tests/components/TicketList.test.tsx
import { render, screen } from '@testing-library/react';
import TicketList from '@/components/TicketList';

test('renders ticket list', () => {
    const tickets = [
        { id: 1, title: 'Test Ticket', status: 'open' }
    ];
    
    render(<TicketList tickets={tickets} />);
    
    expect(screen.getByText('Test Ticket')).toBeInTheDocument();
});
```

## Debugging

### Laravel Debugging

#### Telescope (Development)
```bash
# Install Telescope
composer require laravel/telescope --dev

# Publish configuration
php artisan telescope:install

# Access Telescope
http://localhost:8000/telescope
```

#### Logging
```php
// Use Laravel's logging
Log::info('Ticket created', ['ticket_id' => $ticket->id]);
Log::error('Failed to process ticket', ['error' => $e->getMessage()]);
```

### Frontend Debugging

#### React DevTools
- Install React Developer Tools browser extension
- Use for component inspection and state debugging

#### Console Logging
```typescript
// Use console for debugging
console.log('Ticket data:', ticket);
console.error('Error occurred:', error);

// Remove console statements before production
```

## Performance Optimization

### Backend Optimization

#### Database Queries
```php
// Use eager loading to prevent N+1 queries
$tickets = Ticket::with(['user', 'comments'])->get();

// Use database indexes
// Add indexes in migrations for frequently queried columns

// Use query optimization
$tickets = Ticket::where('status', 'open')
    ->where('tenant_id', tenant()->id)
    ->select(['id', 'title', 'status'])
    ->get();
```

#### Caching
```php
// Use Laravel's cache
$tickets = Cache::remember('tickets.' . tenant()->id, 3600, function () {
    return Ticket::all();
});

// Clear cache when data changes
Cache::forget('tickets.' . tenant()->id);
```

### Frontend Optimization

#### Code Splitting
```typescript
// Use lazy loading for routes
const Dashboard = lazy(() => import('./pages/Dashboard'));

// Use React.lazy for components
const TicketModal = lazy(() => import('./components/TicketModal'));
```

#### Bundle Optimization
```bash
# Analyze bundle size
npm run build -- --analyze

# Optimize images and assets
# Use WebP format for images
# Compress assets
```

## Deployment

### Production Checklist

#### Environment Setup
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure database credentials
- [ ] Set up Redis for caching
- [ ] Configure queue workers

#### Build Process
```bash
# Install production dependencies
composer install --optimize-autoloader --no-dev
npm ci
npm run build

# Run migrations
php artisan migrate --force

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Server Configuration
- [ ] Set up web server (Nginx/Apache)
- [ ] Configure SSL certificates
- [ ] Set up queue workers
- [ ] Configure monitoring
- [ ] Set up backups

## Troubleshooting

### Common Issues

#### Multi-Tenancy Issues
```php
// Check if tenant is set
if (!tenant()) {
    throw new TenantNotFoundException();
}

// Debug tenant context
dd(tenant()->toArray());
```

#### Inertia.js Issues
```typescript
// Check if Inertia is properly configured
import { router } from '@inertiajs/react';

// Debug Inertia requests
router.on('start', (event) => {
    console.log('Inertia request started:', event);
});
```

#### Build Issues
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild frontend
npm run build
```

---

This development guide provides comprehensive information for developers working on the CussUp project. Follow these standards and practices to maintain code quality and consistency across the team. 