import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
// import { dashboard } from '@/routes/index'; // Comentado temporalmente para evitar errores
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { 
    Archive,
    BarChart3,
    BellRing,
    BookOpen,
    Briefcase,
    Database,
    Download,
    File,
    FileText,
    FileType,
    Folder,
    FolderTree,
    GitBranch,
    HelpCircle,
    Key,
    LayoutGrid,
    Search,
    Settings,
    Shield,
    TrendingUp,
    Users,
    Zap,
} from 'lucide-react';
import AppLogo from './app-logo';
import SupportModal from './support-modal';
import { useState, useMemo } from 'react';
import { usePermissions } from '@/hooks/usePermissions';

// Definir items del menú con sus permisos requeridos
const getMainNavItems = (): NavItem[] => [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
        // Dashboard accesible para todos los usuarios autenticados
    },
    {
        title: 'Dashboard Ejecutivo',
        href: '/admin/dashboard-ejecutivo',
        icon: TrendingUp,
        permission: 'administracion.dashboard.ver',
    },
    {
        title: 'Administración',
        href: '#', // No es clickeable directamente
        icon: Settings,
        permission: 'administracion.dashboard.ver', // Al menos ver administración
        items: [
            {
                title: 'Gestión de usuarios',
                href: '/admin/users',
                icon: Users,
                permission: 'usuarios.ver',
            },
            {
                title: 'Tablas de Retención Documental',
                href: '/admin/trd',
                icon: FileText,
                permission: 'trd.ver',
            },
            {
                title: 'Series Documentales',
                href: '/admin/series',
                icon: Folder,
                permission: 'series.ver',
            },
            {
                title: 'Subseries Documentales',
                href: '/admin/subseries',
                icon: BookOpen,
                permission: 'subseries.ver',
            },
            {
                title: 'Cuadros de Clasificación Documental',
                href: '/admin/ccd',
                icon: FolderTree,
                permission: 'ccd.ver',
            },
            {
                title: 'Expedientes Electrónicos',
                href: '/admin/expedientes',
                icon: Briefcase,
                permission: 'expedientes.ver',
            },
            {
                title: 'Documentos',
                href: '/admin/documentos',
                icon: File,
                permission: 'documentos.ver',
            },
            {
                title: 'Plantillas Documentales',
                href: '/admin/plantillas',
                icon: FileType,
                permission: 'plantillas.ver',
            },
            {
                title: 'Préstamos y Consultas',
                href: '/admin/prestamos',
                icon: Users,
                permission: 'prestamos.ver',
            },
            {
                title: 'Disposición Final',
                href: '/admin/disposiciones',
                icon: Archive,
                permission: 'disposiciones.ver',
            },
            {
                title: 'Reportes y Estadísticas',
                href: '/admin/reportes',
                icon: BarChart3,
                permission: 'reportes.ver',
            },
            {
                title: 'Centro de Notificaciones',
                href: '/admin/notificaciones',
                icon: BellRing,
                permission: 'notificaciones.gestionar',
            },
            {
                title: 'Índices Electrónicos',
                href: '/admin/indices',
                icon: Database,
                permission: 'indices.ver',
            },
            {
                title: 'Firmas Digitales',
                href: '/admin/firmas/dashboard',
                icon: Shield,
                permission: 'firmas.gestionar',
            },
            {
                title: 'Workflow de Aprobaciones',
                href: '/admin/workflow',
                icon: GitBranch,
                permission: 'workflow.gestionar',
            },
            {
                title: 'Servicios Externos',
                href: '/admin/servicios-externos',
                icon: BellRing,
                permission: 'administracion.configuracion.gestionar',
            },
            {
                title: 'Optimización del Sistema',
                href: '/admin/optimizacion',
                icon: Zap,
                permission: 'administracion.configuracion.gestionar',
            },
            {
                title: 'API Tokens',
                href: '/admin/api-tokens',
                icon: Key,
                permission: 'api.gestionar',
            },
            {
                title: 'Certificados Digitales',
                href: '/admin/certificados',
                icon: Shield,
                permission: 'certificados.gestionar',
            },
            {
                title: 'Auditoría Avanzada',
                href: '/admin/auditoria',
                icon: Search,
                permission: 'auditoria.ver',
            },
            {
                title: 'Migración e Importación',
                href: '/admin/importaciones',
                icon: Download,
                permission: 'importacion.gestionar',
            },
            {
                title: 'Configuración del Sistema',
                href: '/admin/configuracion',
                icon: Settings,
                permission: 'administracion.configuracion.gestionar',
            },
        ],
    },
];

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    const [showSupportModal, setShowSupportModal] = useState(false);
    const { hasPermission } = usePermissions();

    // Filtrar items del menú según permisos del usuario
    const filteredNavItems = useMemo(() => {
        const filterItems = (items: NavItem[]): NavItem[] => {
            return items.filter(item => {
                // Si no tiene permiso definido, es accesible para todos
                if (!item.permission) {
                    // Si tiene sub-items, filtrarlos también
                    if (item.items) {
                        const filteredSubItems = filterItems(item.items);
                        // Solo mostrar el item padre si tiene sub-items visibles
                        if (filteredSubItems.length === 0) {
                            return false;
                        }
                        // Retornar el item con los sub-items filtrados
                        return { ...item, items: filteredSubItems };
                    }
                    return true;
                }
                
                // Verificar si el usuario tiene el permiso
                const hasAccess = hasPermission(item.permission);
                
                if (hasAccess && item.items) {
                    // Filtrar sub-items también
                    const filteredSubItems = filterItems(item.items);
                    return { ...item, items: filteredSubItems };
                }
                
                return hasAccess;
            }).map(item => {
                // Asegurar que los sub-items también estén filtrados
                if (item.items) {
                    return { ...item, items: filterItems(item.items) };
                }
                return item;
            });
        };

        return filterItems(getMainNavItems());
    }, [hasPermission]);

    return (
        <>
            <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={filteredNavItems} />
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
