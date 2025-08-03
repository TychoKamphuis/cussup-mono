import AppLogoIcon from './app-logo-icon';
import { TenantSelector } from './tenant-selector';

interface AppLogoProps {
    currentTenant?: any;
    tenants?: any[];
}

export default function AppLogo({ currentTenant, tenants }: AppLogoProps) {
    return (
        <>
        
            <div className="ml-1 grid flex-1 text-left text-sm">
                <TenantSelector 
                    currentTenant={currentTenant} 
                    tenants={tenants}
                    className="mt-0"
                />
            </div>
        </>
    );
}
