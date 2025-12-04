import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { Toaster } from '@/components/ui/toast';
import SessionTimeout from '@/components/SessionTimeout';
import AccessDeniedModal from '@/components/access-denied-modal';
import { type BreadcrumbItem } from '@/types';
import { type PropsWithChildren } from 'react';
import { usePage } from '@inertiajs/react';

export default function AppSidebarLayout({ children, breadcrumbs = [] }: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
    const { props } = usePage();
    const sessionData = (props as any).session;

    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar" className="overflow-x-hidden">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
            <Toaster position="top-right" expand={false} richColors />
            
            {/* Componente de timeout de sesión */}
            <SessionTimeout 
                timeoutMinutes={sessionData?.timeout_minutes || 10}
                warningMinutes={2}
            />
            
            {/* Modal de acceso denegado */}
            <AccessDeniedModal />
        </AppShell>
    );
}
