# CussUp - Multi-Tenant Support System

A modern, scalable support system built with Laravel, React, and Inertia.js featuring single-database multi-tenancy architecture.

## 🚀 Overview

CussUp is a comprehensive support system designed to handle multiple tenants (organizations) within a single application instance. Built with Laravel 12, React 19, and Inertia.js, it provides a seamless full-stack development experience with modern UI components and robust multi-tenancy support.

## ✨ Features

### Core Features
- **Multi-Tenant Architecture**: Single database multi-tenancy using UUID-based tenant identification
- **Modern Tech Stack**: Laravel 12, React 19, TypeScript, Tailwind CSS
- **Inertia.js Integration**: Seamless SPA experience without API complexity
- **Authentication System**: Complete user registration, login, and email verification
- **Responsive Design**: Mobile-first design with modern UI components
- **Dark/Light Mode**: Built-in theme switching with system preference detection

### Multi-Tenancy Features
- **UUID-based Tenant Routing**: Each tenant identified by unique UUID in URL
- **Tenant Isolation**: Automatic data scoping and tenant context management
- **Queue Awareness**: Background jobs automatically tenant-aware
- **Flexible Tenant Finder**: Custom tenant resolution logic

### Development Features
- **TypeScript Support**: Full TypeScript integration for type safety
- **Hot Module Replacement**: Fast development with Vite
- **ESLint & Prettier**: Code formatting and linting
- **Testing Setup**: Pest PHP testing framework
- **SSR Support**: Server-side rendering capabilities

## 🛠 Tech Stack

### Backend
- **Laravel 12**: PHP framework
- **Spatie Laravel Multitenancy**: Multi-tenancy package
- **Spatie Laravel Data**: Data transfer objects
- **Inertia.js**: Full-stack framework
- **Ziggy**: Route generation for JavaScript

### Frontend
- **React 19**: UI library
- **TypeScript**: Type safety
- **Tailwind CSS 4**: Utility-first CSS framework
- **Radix UI**: Accessible component primitives
- **Lucide React**: Icon library
- **Vite**: Build tool and dev server

### Development Tools
- **Pest**: Testing framework
- **ESLint**: Code linting
- **Prettier**: Code formatting
- **Laravel Sail**: Docker development environment

## 📁 Project Structure

```
cussup-mono/
├── app/
│   ├── Http/
│   │   ├── Controllers/          # Laravel controllers
│   │   ├── Middleware/           # Custom middleware
│   │   ├── Requests/             # Form request validation
│   │   └── Utils/
│   │       └── UuidTenantFinder.php  # Custom tenant finder
│   ├── Models/
│   │   ├── Tenant.php            # Tenant model
│   │   └── User.php              # User model
│   └── Providers/
├── config/
│   └── multitenancy.php          # Multi-tenancy configuration
├── resources/
│   └── js/
│       ├── components/           # React components
│       ├── hooks/               # Custom React hooks
│       ├── layouts/             # Page layouts
│       ├── pages/               # Inertia pages
│       └── types/               # TypeScript definitions
└── routes/                      # Laravel routes
```

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Node.js 18+
- Composer
- MySQL/PostgreSQL

### Installation

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

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database**
   ```bash
   # Update .env with your database credentials
   php artisan migrate
   ```

6. **Start development servers**
   ```bash
   # Start all services (Laravel, Vite, Queue)
   composer run dev
   
   # Or start individually:
   php artisan serve
   npm run dev
   php artisan queue:work
   ```

## 🏗 Multi-Tenancy Architecture

### Tenant Identification
The system uses UUID-based tenant identification where each tenant is identified by a unique UUID in the URL path:

```
https://yourdomain.com/{tenant-uuid}/dashboard
https://yourdomain.com/{tenant-uuid}/settings
```

### Tenant Finder
The `UuidTenantFinder` class automatically resolves tenants from the URL:

```php
// app/Http/Utils/UuidTenantFinder.php
public function findForRequest(Request $request): ?IsTenant
{
    $segments = $request->segments();
    $uuid = $segments[0] ?? null;
    
    if (!$uuid || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid)) {
        return null;
    }
    
    return app(IsTenant::class)::whereUuid($uuid)->first();
}
```

### Tenant Model
The `Tenant` model extends Spatie's base tenant model with custom UUID functionality:

```php
// app/Models/Tenant.php
class Tenant extends ModelsTenant
{
    public function scopeWhereUuid($query, $uuid)
    {
        return $query->where('uuid', $uuid);
    }
}
```

## 🎨 Frontend Architecture

### Component Structure
The frontend uses a modular component architecture with:

- **UI Components**: Reusable Radix UI-based components
- **Layout Components**: Page layout wrappers
- **Feature Components**: Domain-specific components
- **Hooks**: Custom React hooks for shared logic

### Theme System
Built-in dark/light mode with system preference detection:

```typescript
// resources/js/hooks/use-appearance.tsx
export function useAppearance() {
    const [theme, setTheme] = useState<'light' | 'dark'>('light');
    
    // Theme switching logic
    const toggleTheme = () => {
        const newTheme = theme === 'light' ? 'dark' : 'light';
        setTheme(newTheme);
        document.documentElement.classList.toggle('dark');
    };
    
    return { theme, toggleTheme };
}
```

## 🔧 Configuration

### Multi-Tenancy Configuration
The multi-tenancy is configured in `config/multitenancy.php`:

```php
return [
    'tenant_finder' => UuidTenantFinder::class,
    'tenant_model' => Tenant::class,
    'queues_are_tenant_aware_by_default' => true,
    'current_tenant_context_key' => 'tenantId',
    'current_tenant_container_key' => 'currentTenant',
];
```

### Environment Variables
Key environment variables for multi-tenancy:

```env
APP_NAME="CussUp Support System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cussup
DB_USERNAME=root
DB_PASSWORD=
```

## 🧪 Testing

### Running Tests
```bash
# Run all tests
composer test

# Run specific test suite
php artisan test --filter=Auth
```

### Test Structure
- **Feature Tests**: End-to-end functionality tests
- **Unit Tests**: Individual component tests
- **Pest Framework**: Modern PHP testing with expressive syntax

## 📦 Available Scripts

### Composer Scripts
```bash
composer dev          # Start all development services
composer dev:ssr      # Start with SSR support
composer test         # Run test suite
```

### NPM Scripts
```bash
npm run dev          # Start Vite development server
npm run build        # Build for production
npm run build:ssr    # Build with SSR support
npm run lint         # Run ESLint
npm run format       # Format code with Prettier
npm run types        # TypeScript type checking
```

## 🚀 Deployment

### Production Build
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

### Environment Considerations
- Ensure proper database configuration
- Set up queue workers for background jobs
- Configure web server for Inertia.js
- Set up SSL certificates for production

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Code Style
- Follow PSR-12 coding standards
- Use TypeScript for all JavaScript code
- Write tests for new features
- Update documentation as needed

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
- Create an issue in the repository
- Check the documentation
- Review existing issues and discussions

---

**Built with ❤️ using Laravel, React, and Inertia.js** 