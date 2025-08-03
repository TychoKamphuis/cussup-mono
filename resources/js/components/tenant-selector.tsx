import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ChevronDown, Building2 } from 'lucide-react';

interface Tenant {
    id: number;
    name: string;
    uuid: string;
    domain?: string;
}

interface TenantSelectorProps {
    currentTenant?: Tenant;
    tenants?: Tenant[];
    className?: string;
}

export function TenantSelector({ currentTenant, tenants = [], className = '' }: TenantSelectorProps) {
    const [selectedTenant, setSelectedTenant] = useState<Tenant | undefined>(currentTenant);
    const [isLoading, setIsLoading] = useState(false);

    useEffect(() => {
        setSelectedTenant(currentTenant);
    }, [currentTenant]);

    const handleTenantChange = (tenantId: string) => {
        const tenant = tenants.find(t => t.id.toString() === tenantId);
        if (tenant && tenant.id !== selectedTenant?.id) {
            setIsLoading(true);
            setSelectedTenant(tenant);
            
            router.post('/tenants/switch', {
                tenant_id: tenant.id
            }, {
                onFinish: () => setIsLoading(false),
                onError: () => {
                    setSelectedTenant(currentTenant);
                    setIsLoading(false);
                }
            });
        }
    };

    if (tenants.length === 0) {
        return null;
    }

    return (
        <div className={className}>
            <Select
                value={selectedTenant?.id.toString()}
                onValueChange={handleTenantChange}
                disabled={isLoading}
            >
                <SelectTrigger className="w-[180px] h-8 text-xs">
                    <SelectValue placeholder="Select tenant" />
                </SelectTrigger>
                <SelectContent>
                    {tenants.map((tenant) => (
                        <SelectItem key={tenant.id} value={tenant.id.toString()}>
                            {tenant.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
} 