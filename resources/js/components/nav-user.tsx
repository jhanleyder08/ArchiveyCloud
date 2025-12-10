import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem, useSidebar } from '@/components/ui/sidebar';
import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';
import { useIsMobile } from '@/hooks/use-mobile';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { ChevronsUpDown, Circle } from 'lucide-react';

export function NavUser() {
    const { auth } = usePage<SharedData>().props;
    const { state } = useSidebar();
    const isMobile = useIsMobile();

    // Verificar que auth y user existan
    if (!auth?.user) {
        return null;
    }

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton 
                            size="lg" 
                            className="
                                group relative overflow-hidden
                                text-sidebar-accent-foreground 
                                data-[state=open]:bg-sidebar-accent
                                hover:bg-accent/50
                                transition-all duration-200
                                hover:shadow-md
                                border border-transparent
                                hover:border-border/50
                                group-data-[collapsible=icon]:justify-center
                                group-data-[collapsible=icon]:w-10 group-data-[collapsible=icon]:h-10
                                before:absolute before:inset-0 
                                before:bg-gradient-to-r before:from-transparent 
                                before:via-white/5 before:to-transparent
                                before:translate-x-[-100%] hover:before:translate-x-[100%]
                                before:transition-transform before:duration-700
                            "
                        >
                            <UserInfo user={auth.user} />
                            <div className="ml-auto flex items-center gap-1 group-data-[collapsible=icon]:hidden">
                                <Circle className="h-2 w-2 fill-green-500 text-green-500 animate-pulse" />
                                <ChevronsUpDown className="h-4 w-4 transition-transform group-hover:scale-110" />
                            </div>
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="
                            w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg
                            border-border/50 shadow-xl
                            animate-in fade-in-0 zoom-in-95 slide-in-from-top-2
                            duration-200
                        "
                        align="end"
                        side={isMobile ? 'bottom' : state === 'collapsed' ? 'left' : 'bottom'}
                    >
                        <UserMenuContent user={auth.user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
