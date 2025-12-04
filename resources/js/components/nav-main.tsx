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
        const hrefString = typeof href === 'string' ? href : (href as any)?.url || href;
        return page.url.startsWith(hrefString);
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
                                            {item.items.map((subItem) => {
                                                const hrefString = typeof subItem.href === 'string' ? subItem.href : (subItem.href as any)?.url || subItem.href;
                                                return (
                                                    <SidebarMenuSubItem key={subItem.title}>
                                                        <SidebarMenuSubButton
                                                            asChild
                                                            isActive={isActive(subItem.href)}
                                                        >
                                                            <Link href={hrefString}>
                                                                {subItem.icon && <subItem.icon />}
                                                                <span>{subItem.title}</span>
                                                            </Link>
                                                        </SidebarMenuSubButton>
                                                    </SidebarMenuSubItem>
                                                );
                                            })}
                                        </SidebarMenuSub>
                                    </CollapsibleContent>
                                </SidebarMenuItem>
                            </Collapsible>
                        );
                    } else {
                        // Regular item
                        const hrefString = typeof item.href === 'string' ? item.href : (item.href as any)?.url || item.href;
                        return (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton
                                    isActive={isActive(item.href)}
                                    tooltip={{ children: item.title }}
                                    asChild
                                >
                                    <Link href={hrefString}>
                                        {item.icon && <item.icon />}
                                        <span>{item.title}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        );
                    }
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
