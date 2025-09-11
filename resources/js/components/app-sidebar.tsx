import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { BookOpen, Folder, LayoutGrid, Settings, Users, HelpCircle } from 'lucide-react';
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
