import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { edit } from '@/routes/admin/products';
import { index as collectionsIndex } from '@/routes/admin/products/collections';
import { index as productIdentifiersIndex } from '@/routes/admin/products/identifiers';
import { index as inventoryIndex } from '@/routes/admin/products/inventory';
import { index as mediaIndex } from '@/routes/admin/products/media';
import { index as pricingIndex } from '@/routes/admin/products/pricing';
import { index as variantIndex } from '@/routes/admin/products/variants';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

export default function ProductsLayout({
    children,
    id_record,
    with_variants = false,
}: PropsWithChildren<{
    id_record: number;
    with_variants?: boolean;
}>) {
    // When server-side rendering, we only render the layout on the client...
    if (typeof window === 'undefined') {
        return null;
    }

    const sidebarNavItems: NavItem[] = [
        {
            title: 'Basic Information',
            href: edit(id_record),
            icon: null,
        },
        {
            title: 'Media',
            href: mediaIndex(id_record),
            icon: null,
        },
        {
            title: 'Variants',
            href: variantIndex(id_record),
            icon: null,
        },
        {
            title: 'Collections',
            href: collectionsIndex(id_record),
            icon: null,
        },
    ];

    if (!with_variants) {
        const extraSidebarNavItems: NavItem[] = [
            {
                title: 'Pricing',
                href: pricingIndex(id_record),
                icon: null,
            },
            {
                title: 'Product Identifiers',
                href: productIdentifiersIndex(id_record),
                icon: null,
            },
            {
                title: 'Inventory',
                href: inventoryIndex(id_record),
                icon: null,
            },
        ];

        extraSidebarNavItems.forEach((item) => sidebarNavItems.push(item));
    }

    const currentPath = window.location.pathname;

    return (
        <div className="px-4 py-6">
            <Heading title="Product Management" description="Manage system's products" />

            <div className="flex flex-col lg:flex-row lg:space-x-12">
                <aside className="w-full max-w-xl lg:w-48">
                    <nav className="flex flex-col space-y-1 space-x-0">
                        {sidebarNavItems.map((item, index) => (
                            <Button
                                key={`${typeof item.href === 'string' ? item.href : item.href?.url}-${index}`}
                                size="sm"
                                variant="ghost"
                                asChild
                                className={cn('w-full justify-start', {
                                    'bg-muted': currentPath === (typeof item.href === 'string' ? item.href : item.href?.url),
                                })}
                            >
                                <Link href={item.href} prefetch>
                                    {item.icon && <item.icon className="h-4 w-4" />}
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className="flex-1 md:max-w-3xl">
                    <section className="max-w-3xl space-y-12">{children}</section>
                </div>
            </div>
        </div>
    );
}
