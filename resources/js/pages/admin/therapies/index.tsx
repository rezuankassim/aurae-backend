import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { create, index, reorder as reorderRoute } from '@/routes/admin/therapies';
import { Therapy, type BreadcrumbItem } from '@/types';
import { DndContext, DragEndEvent, PointerSensor, closestCenter, useSensor, useSensors } from '@dnd-kit/core';
import { SortableContext, arrayMove, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Head, Link, router } from '@inertiajs/react';
import { GripVertical } from 'lucide-react';
import { useState } from 'react';
import { columns } from './columns';
import { DataTable } from './data-table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Therapies',
        href: index().url,
    },
];

function SortableTherapyItem({ therapy }: { therapy: Therapy }) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: therapy.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            className={`mb-3 flex items-center gap-3 rounded-lg border p-4 shadow-sm transition-shadow hover:shadow-md ${
                isDragging ? 'opacity-50 shadow-lg' : ''
            }`}
        >
            <div {...attributes} {...listeners} className="flex cursor-grab items-center rounded-md p-2 hover:bg-gray-100 active:cursor-grabbing">
                <GripVertical className="h-5 w-5 text-gray-400" />
            </div>
            <div className="flex flex-1 items-center gap-4">
                {therapy.image_url ? (
                    <img src={therapy.image_url} alt={therapy.name} className="h-16 w-16 rounded-lg border object-cover" />
                ) : (
                    <div className="flex h-16 w-16 items-center justify-center rounded-lg border bg-gray-100">
                        <span className="text-xs text-gray-400">No image</span>
                    </div>
                )}
                <div className="flex-1">
                    <div className="mb-1 font-semibold">{therapy.name}</div>
                    {therapy.description && <div className="text-sm text-muted-foreground">{therapy.description}</div>}
                </div>
                <div className="flex items-center gap-2">
                    <Badge variant={therapy.is_active ? 'default' : 'destructive'}>{therapy.is_active ? 'Active' : 'Inactive'}</Badge>
                </div>
            </div>
        </div>
    );
}

export default function TherapiesIndex({ therapies: initialTherapies }: { therapies: Therapy[] }) {
    const [therapies, setTherapies] = useState(initialTherapies);
    const [isSaving, setIsSaving] = useState(false);
    const [showSortable, setShowSortable] = useState(false);

    const sensors = useSensors(
        useSensor(PointerSensor, {
            activationConstraint: {
                distance: 8,
            },
        }),
    );

    const handleDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;

        if (over && active.id !== over.id) {
            setTherapies((items) => {
                const oldIndex = items.findIndex((item) => item.id === active.id);
                const newIndex = items.findIndex((item) => item.id === over.id);

                return arrayMove(items, oldIndex, newIndex);
            });
        }
    };

    const handleSaveOrder = () => {
        setIsSaving(true);

        const reorderedTherapies = therapies.map((therapy, index) => ({
            id: therapy.id,
            order: index,
        }));

        router.post(
            reorderRoute().url,
            { therapies: reorderedTherapies },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setIsSaving(false);
                    setShowSortable(false);
                },
                onError: () => {
                    setIsSaving(false);
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Therapies" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Therapies" description="Manage therapy of the system and view details" />

                    <div className="flex gap-2">
                        {showSortable ? (
                            <>
                                <Button variant="outline" onClick={() => setShowSortable(false)} disabled={isSaving}>
                                    Cancel
                                </Button>
                                <Button onClick={handleSaveOrder} disabled={isSaving}>
                                    {isSaving ? 'Saving...' : 'Save Order'}
                                </Button>
                            </>
                        ) : (
                            <>
                                <Button variant="outline" onClick={() => setShowSortable(true)}>
                                    Reorder Therapies
                                </Button>
                                <Button asChild>
                                    <Link href={create().url}>Create therapy</Link>
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                {showSortable ? (
                    <Card>
                        <CardContent className="pt-3">
                            <div className="mb-4 text-sm text-muted-foreground">
                                Drag and drop to reorder therapies. The order will be reflected in the mobile app.
                            </div>
                            <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
                                <SortableContext items={therapies.map((t) => t.id)} strategy={verticalListSortingStrategy}>
                                    {therapies.map((therapy) => (
                                        <SortableTherapyItem key={therapy.id} therapy={therapy} />
                                    ))}
                                </SortableContext>
                            </DndContext>
                        </CardContent>
                    </Card>
                ) : (
                    <DataTable columns={columns} data={therapies} />
                )}
            </div>
        </AppLayout>
    );
}
