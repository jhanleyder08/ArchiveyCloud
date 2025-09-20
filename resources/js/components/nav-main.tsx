import { 
    SidebarGroup, 
    SidebarGroupLabel, 
    SidebarMenu, 
    SidebarMenuButton, 
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage, router } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { useState } from 'react';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const page = usePage();
    const [openItems, setOpenItems] = useState<Set<string>>(new Set());

    const toggleItem = (title: string) => {
        const newOpenItems = new Set(openItems);
        if (newOpenItems.has(title)) {
            newOpenItems.delete(title);
        } else {
            newOpenItems.add(title);
        }
        setOpenItems(newOpenItems);
    };

    const isActive = (href: NonNullable<NavItem['href']>) => {
        return page.url.startsWith(typeof href === 'string' ? href : href.url);
    };

    const hasActiveSubItem = (item: NavItem) => {
        if (!item.items) return false;
        return item.items.some(subItem => isActive(subItem.href));
    };

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>Platform</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => {
                    if (item.items && item.items.length > 0) {
                        // Dropdown item
                        const isOpen = openItems.has(item.title);
                        const hasActiveChild = hasActiveSubItem(item);
                        
                        return (
                            <Collapsible key={item.title} open={isOpen} onOpenChange={() => toggleItem(item.title)}>
                                <SidebarMenuItem>
                                    <CollapsibleTrigger asChild>
                                        <SidebarMenuButton
                                            isActive={hasActiveChild}
                                            tooltip={{ children: item.title }}
                                        >
                                            {item.icon && <item.icon />}
                                            <span>{item.title}</span>
                                            <ChevronRight 
                                                className={`ml-auto h-4 w-4 transition-transform ${
                                                    isOpen ? 'rotate-90' : ''
                                                }`} 
                                            />
                                        </SidebarMenuButton>
                                    </CollapsibleTrigger>
                                    <CollapsibleContent>
                                        <SidebarMenuSub>
                                            {item.items.map((subItem) => (
                                                <SidebarMenuSubItem key={subItem.title}>
                                                    <SidebarMenuSubButton
                                                        asChild
                                                        isActive={isActive(subItem.href)}
                                                    >
                                                        <div onClick={() => router.visit(subItem.href as string)}>
                                                            {subItem.icon && <subItem.icon />}
                                                            <span>{subItem.title}</span>
                                                        </div>
                                                    </SidebarMenuSubButton>
                                                </SidebarMenuSubItem>
                                            ))}
                                        </SidebarMenuSub>
                                    </CollapsibleContent>
                                </SidebarMenuItem>
                            </Collapsible>
                        );
                    } else {
                        // Regular item
                        return (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton
                                    isActive={isActive(item.href)}
                                    tooltip={{ children: item.title }}
                                    onMouseEnter={() => console.log('🖱️ Mouse encima de:', item.title)}
                                    onMouseDown={() => console.log('🖱️ Mouse presionado en:', item.title)}
                                    onClick={(e) => {
                                        console.log('🎯 CLICK DETECTADO en:', item.title);
                                        console.log('🎯 Event:', e);
                                        e.preventDefault();
                                        e.stopPropagation();
                                        console.log('🚀 Navegando a:', item.href);
                                        window.location.href = item.href as string;
                                    }}
                                >
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        );
                    }
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
