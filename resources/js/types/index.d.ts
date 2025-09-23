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
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    success: string;
    error: string;
    auth: Auth;
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    phone: string;
    created_at: string;
    updated_at: string;
    is_admin: boolean;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Address {
    id: number;
    is_default: boolean;
    type: string;
    name: string;
    phone: string;
    line1: string;
    line2: string | null;
    line3: string | null;
    city: string;
    state: string | null;
    postal_code: string;
    country: string;
    user_id: number;
    country_label: string;
    state_label: string;
    type_label: string;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface News {
    id: number;
    type: 0 | 1; // 0 = news, 1 = promotion
    title: string;
    content: string;
    html_content: string;
    is_published: boolean;
    published_at: string | null;
    published_time: string | null;
    published_date: string | null;
    image: string | null;
    created_at: string;
    updated_at: string;
    image_url?: string | null;
    [key: string]: unknown; // This allows for additional properties...
}
