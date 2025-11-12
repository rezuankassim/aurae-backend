import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/react';

import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, index } from '@/routes/admin/faqs';

import FAQController from '@/actions/App/Http/Controllers/Admin/FAQController';
import { Card, CardContent } from '@/components/ui/card';
import { Field, FieldError, FieldLabel, FieldLegend } from '@/components/ui/field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Frequently Asked Questions',
        href: index().url,
    },
    {
        title: 'Create FAQ',
        href: create().url,
    },
];

export default function FAQCreate() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create FAQ" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="Create FAQ" description="Create new FAQ for the system" />

                <Form
                    {...FAQController.store.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    resetOnSuccess
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <Card>
                                <CardContent className="space-y-6">
                                    <div className="space-y-6">
                                        <FieldLegend className="sr-only">FAQ</FieldLegend>
                                        <Field>
                                            <FieldLabel htmlFor="question">Question</FieldLabel>
                                            <Input id="question" name="question" placeholder="Question" />

                                            {errors.question ? <FieldError>{errors.question}</FieldError> : null}
                                        </Field>

                                        <Field>
                                            <FieldLabel htmlFor="answer">Answer</FieldLabel>
                                            <Textarea id="answer" name="answer" placeholder="Answer" />

                                            {errors.answer ? <FieldError>{errors.answer}</FieldError> : null}
                                        </Field>

                                        <Field>
                                            <FieldLabel htmlFor="status">Status</FieldLabel>
                                            <Select name="status">
                                                <SelectTrigger id="status" name="status">
                                                    <SelectValue placeholder="Select status" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="0">Inactive</SelectItem>
                                                    <SelectItem value="1">Active</SelectItem>
                                                </SelectContent>
                                            </Select>

                                            {errors.status ? <FieldError>{errors.status}</FieldError> : null}
                                        </Field>
                                    </div>
                                </CardContent>
                            </Card>

                            <Button type="submit" disabled={processing}>
                                Submit
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
