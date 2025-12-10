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
import { Link, usePage } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { useState, useEffect } from 'react';
import { Badge } from '@/components/ui/badge';

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

    // Auto-abrir el item padre si contiene un sub-item activo
    useEffect(() => {
        items.forEach(item => {
            if (hasActiveSubItem(item) && !openItems.has(item.title)) {
                setOpenItems(prev => new Set([...prev, item.title]));
            }
        });
    }, [page.url]);

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel className="text-xs font-semibold uppercase tracking-wider text-muted-foreground/70 group-data-[collapsible=icon]:hidden">
                Menú Principal
            </SidebarGroupLabel>
            <SidebarMenu className="space-y-0.5 group-data-[collapsible=icon]:space-y-3">
                {items.map((item) => {
                    if (item.items && item.items.length > 0) {
                        // Dropdown item
                        const isOpen = openItems.has(item.title);
                        const hasActiveChild = hasActiveSubItem(item);
                        
                        return (
                            <Collapsible 
                                key={item.title} 
                                open={isOpen} 
                                onOpenChange={() => toggleItem(item.title)}
                                className="group/collapsible"
                            >
                                <SidebarMenuItem>
                                    <CollapsibleTrigger asChild>
                                        <SidebarMenuButton
                                            isActive={hasActiveChild}
                                            tooltip={{ children: item.title }}
                                            className={`
                                                group relative overflow-hidden transition-all duration-200
                                                h-9 px-2.5
                                                group-data-[collapsible=icon]:justify-center
                                                group-data-[collapsible=icon]:w-10 group-data-[collapsible=icon]:h-10
                                                ${hasActiveChild 
                                                    ? 'bg-primary/10 text-primary font-medium shadow-sm' 
                                                    : 'hover:bg-accent/50 hover:text-accent-foreground'
                                                }
                                                before:absolute before:inset-0 before:bg-gradient-to-r 
                                                before:from-transparent before:via-white/5 before:to-transparent
                                                before:translate-x-[-100%] hover:before:translate-x-[100%]
                                                before:transition-transform before:duration-700
                                            `}
                                        >
                                            {item.icon && (
                                                <item.icon className={`
                                                    h-4 w-4 shrink-0
                                                    transition-all duration-200 group-hover:scale-110
                                                    group-data-[collapsible=icon]:h-5 group-data-[collapsible=icon]:w-5
                                                    ${hasActiveChild ? 'text-primary' : ''}
                                                `} />
                                            )}
                                            <span className="flex-1 truncate text-sm group-data-[collapsible=icon]:hidden">{item.title}</span>
                                            {item.items.length > 0 && (
                                                <Badge 
                                                    variant="secondary" 
                                                    className="ml-auto mr-2 h-5 min-w-[20px] px-1 text-[10px] font-medium group-data-[collapsible=icon]:hidden"
                                                >
                                                    {item.items.length}
                                                </Badge>
                                            )}
                                            <ChevronRight 
                                                className={`
                                                    h-4 w-4 transition-all duration-300 ease-out
                                                    group-data-[collapsible=icon]:hidden
                                                    ${isOpen ? 'rotate-90 text-primary' : 'group-hover:translate-x-0.5'}
                                                `} 
                                            />
                                        </SidebarMenuButton>
                                    </CollapsibleTrigger>
                                    <CollapsibleContent className="transition-all duration-300 ease-out data-[state=closed]:animate-accordion-up data-[state=open]:animate-accordion-down">
                                        <SidebarMenuSub className="border-l-2 border-muted/50 ml-4 pl-0 pt-1 pb-1 space-y-0">
                                            {item.items.map((subItem) => {
                                                const hrefString = typeof subItem.href === 'string' ? subItem.href : (subItem.href as any)?.url || subItem.href;
                                                const isSubActive = isActive(subItem.href);
                                                
                                                return (
                                                    <SidebarMenuSubItem key={subItem.title}>
                                                        <SidebarMenuSubButton
                                                            asChild
                                                            isActive={isSubActive}
                                                            className={`
                                                                group/sub relative overflow-hidden transition-all duration-200
                                                                h-8 px-2
                                                                ${isSubActive 
                                                                    ? 'bg-primary/5 text-primary font-medium border-l-2 border-primary ml-[-2px]' 
                                                                    : 'hover:bg-accent/40 hover:text-accent-foreground hover:translate-x-0.5 border-l-2 border-transparent ml-[-2px]'
                                                                }
                                                            `}
                                                        >
                                                            <Link href={hrefString}>
                                                                {subItem.icon && (
                                                                    <subItem.icon className={`
                                                                        h-3.5 w-3.5 shrink-0 transition-all duration-200 
                                                                        ${isSubActive ? 'text-primary scale-110' : 'group-hover/sub:scale-110'}
                                                                    `} />
                                                                )}
                                                                <span className="text-xs truncate">{subItem.title}</span>
                                                                {isSubActive && (
                                                                    <span className="ml-auto h-1.5 w-1.5 rounded-full bg-primary animate-pulse" />
                                                                )}
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
                        const itemActive = isActive(item.href);
                        
                        return (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton
                                    isActive={itemActive}
                                    tooltip={{ children: item.title }}
                                    asChild
                                    className={`
                                        group relative overflow-hidden transition-all duration-200
                                        h-9 px-2.5
                                        group-data-[collapsible=icon]:justify-center
                                        group-data-[collapsible=icon]:w-10 group-data-[collapsible=icon]:h-10
                                        ${itemActive 
                                            ? 'bg-primary/10 text-primary font-medium shadow-sm' 
                                            : 'hover:bg-accent/50 hover:text-accent-foreground'
                                        }
                                        before:absolute before:inset-0 before:bg-gradient-to-r 
                                        before:from-transparent before:via-white/5 before:to-transparent
                                        before:translate-x-[-100%] hover:before:translate-x-[100%]
                                        before:transition-transform before:duration-700
                                    `}
                                >
                                    <Link href={hrefString}>
                                        {item.icon && (
                                            <item.icon className={`
                                                h-4 w-4 shrink-0
                                                transition-all duration-200 group-hover:scale-110
                                                group-data-[collapsible=icon]:h-5 group-data-[collapsible=icon]:w-5
                                                ${itemActive ? 'text-primary' : ''}
                                            `} />
                                        )}
                                        <span className="truncate text-sm group-data-[collapsible=icon]:hidden">{item.title}</span>
                                        {itemActive && (
                                            <span className="ml-auto h-2 w-2 rounded-full bg-primary animate-pulse shadow-lg shadow-primary/50 group-data-[collapsible=icon]:hidden" />
                                        )}
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
