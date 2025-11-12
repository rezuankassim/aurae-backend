import AppLayout from '@/layouts/app-layout';
import { Faq, type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';

import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index } from '@/routes/admin/faqs';

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
        title: 'Edit FAQ',
        href: '#',
    },
];

export default function FAQEdit({ faq }: { faq: Faq }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit FAQ" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="Edit FAQ" description="Edit existing FAQ for the system" />

                <Form
                    {...FAQController.update.form(faq.id)}
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
                                            <Input id="question" name="question" placeholder="Question" defaultValue={faq.question} />

                                            {errors.question ? <FieldError>{errors.question}</FieldError> : null}
                                        </Field>

                                        <Field>
                                            <FieldLabel htmlFor="answer">Answer</FieldLabel>
                                            <Textarea id="answer" name="answer" placeholder="Answer" defaultValue={faq.answer} />

                                            {errors.answer ? <FieldError>{errors.answer}</FieldError> : null}
                                        </Field>

                                        <Field>
                                            <FieldLabel htmlFor="status">Status</FieldLabel>
                                            <Select name="status" defaultValue={String(faq.status)}>
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

                            <div className="flex items-center gap-2">
                                <Button variant="outline" asChild>
                                    <Link href={index().url}>Back</Link>
                                </Button>

                                <Button type="submit" disabled={processing}>
                                    Submit
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
