import { Head } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Building2, Check } from 'lucide-react';
import { router } from '@inertiajs/react';

interface Tenant {
    id: number;
    name: string;
    uuid: string;
    domain?: string;
}

interface TenantSelectorPageProps {
    tenants: Tenant[];
    currentTenant?: Tenant;
}

export default function TenantSelectorPage({ tenants, currentTenant }: TenantSelectorPageProps) {
    const handleTenantSelect = (tenant: Tenant) => {
        router.post('/tenants/switch', {
            tenant_id: tenant.id
        });
    };

    return (
        <>
            <Head title="Select Tenant" />
            
            <div className="container mx-auto max-w-2xl py-8">
                <div className="text-center mb-8">
                    <h1 className="text-3xl font-bold">Select Your Tenant</h1>
                    <p className="text-muted-foreground mt-2">
                        Choose which tenant you'd like to work with
                    </p>
                </div>

                <div className="grid gap-4">
                    {tenants.map((tenant) => (
                        <Card 
                            key={tenant.id} 
                            className={`cursor-pointer transition-colors hover:bg-muted/50 ${
                                currentTenant?.id === tenant.id ? 'ring-2 ring-primary' : ''
                            }`}
                            onClick={() => handleTenantSelect(tenant)}
                        >
                            <CardHeader className="pb-3">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                                            <Building2 className="h-5 w-5 text-primary" />
                                        </div>
                                        <div>
                                            <CardTitle className="text-lg">{tenant.name}</CardTitle>
                                            <CardDescription>
                                                {tenant.domain || `Tenant ID: ${tenant.uuid}`}
                                            </CardDescription>
                                        </div>
                                    </div>
                                    {currentTenant?.id === tenant.id && (
                                        <div className="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                            <Check className="h-4 w-4" />
                                        </div>
                                    )}
                                </div>
                            </CardHeader>
                        </Card>
                    ))}
                </div>

                {tenants.length === 0 && (
                    <Card>
                        <CardContent className="pt-6">
                            <div className="text-center">
                                <Building2 className="mx-auto h-12 w-12 text-muted-foreground" />
                                <h3 className="mt-4 text-lg font-semibold">No Tenants Available</h3>
                                <p className="mt-2 text-muted-foreground">
                                    You don't have access to any tenants yet.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
} 