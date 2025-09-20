import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { dashboard } from '@/routes/index';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { BookOpen, Folder, LayoutGrid, Settings, Users, HelpCircle, FileText, FolderTree, File, Clock, Bell, Archive, BarChart3, Briefcase, BellRing, Shield, GitBranch } from 'lucide-react';
import AppLogo from './app-logo';
import SupportModal from './support-modal';
import { useState } from 'react';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Administración',
        href: '#', // No es clickeable directamente
        icon: Settings,
        items: [
            {
                title: 'Gestión de usuarios',
                href: '/admin/users',
                icon: Users,
            },
            {
                title: 'Tablas de Retención Documental',
                href: '/admin/trd',
                icon: FileText,
            },
            {
                title: 'Series Documentales',
                href: '/admin/series',
                icon: Folder,
            },
            {
                title: 'Subseries Documentales',
                href: '/admin/subseries',
                icon: BookOpen,
            },
            {
                title: 'Cuadros de Clasificación Documental',
                href: '/admin/ccd',
                icon: FolderTree,
            },
            {
                title: 'Expedientes Electrónicos',
                href: '/admin/expedientes',
                icon: Briefcase,
            },
            {
                title: 'Documentos',
                href: '/admin/documentos',
                icon: File,
            },
            {
                title: 'Préstamos y Consultas',
                href: '/admin/prestamos',
                icon: Users,
            },
            {
                title: 'Disposición Final',
                href: '/admin/disposiciones',
                icon: Archive,
            },
            {
                title: 'Reportes y Estadísticas',
                href: '/admin/reportes',
                icon: BarChart3,
            },
            {
                title: 'Centro de Notificaciones',
                href: '/admin/notificaciones',
                icon: BellRing,
            },
            {
                title: 'Firmas Digitales',
                href: '/admin/firmas/dashboard',
                icon: Shield,
            },
            {
                title: 'Workflow de Aprobaciones',
                href: '/admin/workflow',
                icon: GitBranch,
            },
        ],
    },
];

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    const [showSupportModal, setShowSupportModal] = useState(false);

    return (
        <>
            <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                
                {/* Support Button */}
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            onClick={() => setShowSupportModal(true)}
                            className="text-neutral-600 hover:text-neutral-800 dark:text-neutral-300 dark:hover:text-neutral-100"
                        >
                            <HelpCircle className="h-5 w-5" />
                            <span>Soporte Técnico</span>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                
                <NavUser />
            </SidebarFooter>
        </Sidebar>

        {/* Support Modal */}
        <SupportModal 
            open={showSupportModal} 
            onOpenChange={setShowSupportModal} 
        />
        </>
    );
}
