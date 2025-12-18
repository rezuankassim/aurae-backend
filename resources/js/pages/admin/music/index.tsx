import AppLayout from '@/layouts/app-layout';
import { Music, type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/admin/music';
import { columns } from './columns';
import { DataTable } from './data-table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Music',
        href: index().url,
    },
];

export default function MusicIndex({ music }: { music: Music[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Music" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Music" description="Manage system's music files for therapies" />

                    <Button className="mb-6" asChild>
                        <Link href={create().url}>Add Music</Link>
                    </Button>
                </div>

                <DataTable columns={columns} data={music} />
            </div>
        </AppLayout>
    );
}
