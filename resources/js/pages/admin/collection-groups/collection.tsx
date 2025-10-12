import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Empty, EmptyDescription, EmptyHeader, EmptyTitle } from '@/components/ui/empty';
import { Item, ItemActions, ItemContent, ItemTitle } from '@/components/ui/item';
import { destroy } from '@/routes/admin/collection-groups/collections';
import { Collection as CollectionT } from '@/types';
import { Link } from '@inertiajs/react';
import { MoreVertical } from 'lucide-react';

export function Collection({ collections }: { collections: CollectionT[] }) {
    if (!collections || collections.length === 0) {
        return (
            <Empty>
                <EmptyHeader>
                    <EmptyTitle>No Collections Yet</EmptyTitle>
                    <EmptyDescription>There are no collections under this group yet.</EmptyDescription>
                </EmptyHeader>
            </Empty>
        );
    }

    return (
        <div className="space-y-2">
            {collections.map((collection) => (
                <Item variant="muted" key={collection.id}>
                    <ItemContent>
                        <ItemTitle>{collection.attribute_data.name.en}</ItemTitle>
                    </ItemContent>
                    <ItemActions>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" className="h-8 w-8 p-0">
                                    <span className="sr-only">Open menu</span>
                                    <MoreVertical className="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                <DropdownMenuItem asChild>
                                    <Link
                                        className="w-full hover:cursor-pointer"
                                        href={destroy([collection.collection_group_id, collection.id]).url}
                                        method="delete"
                                    >
                                        Delete
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </ItemActions>
                </Item>
            ))}
        </div>
    );
}
