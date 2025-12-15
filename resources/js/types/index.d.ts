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
    href?: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
    children?: NavItem[];
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
    status: boolean;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Therapy {
    id: number;
    image: string | null;
    name: string;
    description: string | null;
    music: string;
    configuration: Record<string, string>;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    image_url?: string | null;
    music_url?: string | null;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Address {
    id: number;
    shipping_default: boolean;
    billing_default: boolean;
    title: string | null;
    delivery_instructions: string | null;
    first_name: string;
    last_name: string;
    contact_email: string;
    contact_phone: string;
    line_one: string;
    line_two: string | null;
    line_three: string | null;
    city: string;
    state: string | null;
    postcode: string;
    country: { id: number; name: string; iso3: string; iso3: string };
    user_id: number;
    country_label: string;
    state_label: string;
    type_label: string;
    created_at: string;
    updated_at: string;
    stateData: { id: number; name: string; code: string; country_id: number };
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

export interface Knowledge {
    id: number;
    title: string;
    content: string;
    html_content: string;
    is_published: boolean;
    published_at: string | null;
    published_time: string | null;
    published_date: string | null;
    created_at: string;
    updated_at: string;
    video_url: string | null;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Faq {
    id: number;
    status: number;
    question: string;
    answer: string;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Feedback {
    id: number;
    user_id: number;
    user?: User;
    description: string;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface HealthReport {
    id: number;
    user_id: number;
    file: string;
    file_url: string;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface UsageHistory {
    id: string;
    content: string;
    user_id: number;
    created_at: string;
    updated_at: string;
    therapy?: Therapy;
    [key: string]: unknown; // This allows for additional properties...
}

export interface DeviceMaintenance {
    id: number;
    status: 0 | 1 | 2 | 3; // 0: pending, 1: pending_factory, 2: in_progress, 3: completed
    user_id: number;
    user: User;
    maintenance_requested_at: string;
    factory_maintenance_requested_at: string | null;
    requested_at_changes: string | null;
    created_at: string;
    updated_at: string;
    requested_at_changes_formatted: {
        changed_at: string;
        user: User;
        previous_maintenance_requested_at: string;
        new_maintenance_requested_at: string;
        previous_factory_maintenance_requested_at: string;
        new_factory_maintenance_requested_at: string | null;
    }[];
    [key: string]: unknown; // This allows for additional properties...
}

export interface Product {
    id: number;
    product_type_id: number;
    status: 'draft' | 'published';
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    attribute_data: Record<string, any>;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    brand: Record<string, any> | null;
    variants: ProductVariant[];
    product_type: ProductType;
    tags: Tag[];
    tags_array: number[]; // Array of tag IDs
    created_at: string;
    updated_at: string;
    prices?: Price[];
    collections?: Collection[];
    [key: string]: unknown; // This allows for additional properties...
}

export interface ProductVariant {
    id: number;
    product_id: number;
    tax_class_id: number;
    tax_ref: string | null;
    unit_quantity: number;
    sku: string | null;
    gtin: string | null;
    mpn: string | null;
    ean: string | null;
    stock: number;
    backorder: number;
    purchasable: 'always' | 'in_stock' | 'in_stock_or_on_backorder';
    quantity_increment: number;
    min_quantity: number;
    values: ProductOptionValue[];
    base_prices?: Price[];
    tax_class?: TaxClass;
}

export interface ProductType {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Tag {
    id: number;
    value: string;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Media {
    id: number;
    model_id: number;
    uuid: string;
    collection_name: string;
    file_name: string;
    custom_properties: Record<string, unknown>;
    created_at: string;
    updated_at: string;
    url: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface ProductOption {
    id: number;
    name: { en: string; [key: string]: string };
    label: { en: string; [key: string]: string };
    shared: boolean;
    handle: string;
    pivot: { product_id: number; product_option_id: number; position: 1 };
    created_at: string;
    updated_at: string;
    values: ProductOptionValue[];
    [key: string]: unknown; // This allows for additional properties...
}

export interface ProductOptionValue {
    id: number;
    name: { en: string; [key: string]: string };
    option: ProductOption;
    position: number;
    product_option_id: number;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Price {
    id: number;
    currency: Currency;
    currency_id: number;
    price: PriceV | number;
    min_quantity: number;
    compare_price: PriceV | number;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Currency {
    id: number;
    name: string;
    code: string;
    decimal_places: number;
    default: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface PriceV {
    currency: Currency;
    unitQty: number;
    value: number;
}

export interface LoginActivity {
    id: number;
    user_id: number;
    guard: string;
    session_id: string | null;
    ip_address: string | null;
    user_agent: string | null;
    occurred_at: string | null;
    logout_at: string | null;
    succeeded: number;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface TaxClass {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
    default: boolean;
    [key: string]: unknown; // This allows for additional properties...
}

export interface CollectionGroup {
    id: number;
    name: string;
    handle: string;
    collections_count?: number;
    created_at: string;
    updated_at: string;
    collections?: Collection[];
    [key: string]: unknown; // This allows for additional properties...
}

export interface Collection {
    id: number;
    collection_group_id: number;
    _lft: number;
    _rgt: number;
    parent_id: number | null;
    type: string;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    attribute_data: Record<string, any>;
    sort: string;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    pivot: { product_id: number; collection_id: number; position: number };
    [key: string]: unknown; // This allows for additional properties...
}

export interface SocialMedia {
    id: number;
    links: Record<string, string>;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}
