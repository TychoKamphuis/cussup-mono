import { router } from '@inertiajs/react';

// Configure Inertia to include tenant header in all requests
router.on('before', (event) => {
    const tenantUuid = (window as any).__TENANT_UUID__;
    if (tenantUuid) {
        // Add the tenant header to the request
        const options = event.detail.options || {};
        const headers = options.headers || {};
        headers['X-Tenant-ID'] = tenantUuid;
        options.headers = headers;
        event.detail.options = options;
    }
});

export { router }; 