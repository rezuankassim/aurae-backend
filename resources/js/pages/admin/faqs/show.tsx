import AppLayout from '@/layouts/app-layout';
import { Faq, type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';

import FAQController from '@/actions/App/Http/Controllers/Admin/FAQController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/admin/faqs';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Frequently Asked Questions',
        href: index().url,
    },
    {
        title: 'Show FAQ',
        href: '#',
    },
];

export default function ShowFAQ({ faq }: { faq: Faq }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Show FAQ" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Show FAQ" description="View system FAQ information" />

                    <Dialog>
                        <DialogTrigger asChild>
                            <Button className="mb-6" variant="destructive">
                                Delete FAQ
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>Are you sure you want to delete this record?</DialogTitle>
                            <DialogDescription>
                                Once the record is deleted, all of its resources and data will also be permanently deleted.
                            </DialogDescription>

                            <Form
                                {...FAQController.destroy.form(faq.id)}
                                options={{
                                    preserveScroll: true,
                                }}
                                resetOnSuccess
                                className="space-y-6"
                            >
                                {({ processing }) => (
                                    <>
                                        <DialogFooter className="gap-2">
                                            <DialogClose asChild>
                                                <Button variant="secondary">Cancel</Button>
                                            </DialogClose>

                                            <Button variant="destructive" disabled={processing} asChild>
                                                <button type="submit">Delete</button>
                                            </Button>
                                        </DialogFooter>
                                    </>
                                )}
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>

                <Card>
                    <CardContent className="space-y-6">
                        <div className="space-y-6">
                            <div className="grid gap-2">
                                <Label>Question</Label>

                                <p>{faq.question}</p>
                            </div>

                            <div className="grid gap-2">
                                <Label>Answer</Label>

                                <p>{faq.answer}</p>
                            </div>

                            <div className="grid gap-2">
                                <Label>Status</Label>

                                <div>{faq.status ? <Badge>Active</Badge> : <Badge variant="secondary">Inactive</Badge>}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex">
                    <Button variant="outline" asChild>
                        <Link href={index().url}>Back</Link>
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}
