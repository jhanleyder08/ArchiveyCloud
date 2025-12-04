import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
    items?: NavItem[]; // Para sub-elementos dropdown
    permission?: string; // Permiso requerido para ver este item
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at?: string;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

// Global function declarations
declare global {
    function route(name?: string, params?: any): string;
    
    interface Window {
        route: typeof route;
        Ziggy: any;
        __accessDeniedHandler: ((message?: string) => void) | null;
        __setAccessDeniedHandler: ((handler: (message?: string) => void) => void) | undefined;
    }
}
