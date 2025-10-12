import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/react';

import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { create, index } from '@/routes/admin/news';

import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { SerializedEditorState, SerializedLexicalNode } from 'lexical';
import { ChevronDown } from 'lucide-react';
import { SetStateAction, useState } from 'react';

import KnowledgeController from '@/actions/App/Http/Controllers/Admin/KnowledgeController';
import { Editor } from '@/components/editor-00/editor';
import { Card, CardContent } from '@/components/ui/card';
import { Field, FieldError, FieldLabel, FieldLegend } from '@/components/ui/field';
import dayjs from 'dayjs';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'News',
        href: index().url,
    },
    {
        title: 'Create news',
        href: create().url,
    },
];

export default function KnowledgeCreate() {
    const initialValue = {
        root: {
            children: [
                {
                    children: [],
                    direction: 'ltr',
                    format: '',
                    indent: 0,
                    type: 'paragraph',
                    version: 1,
                },
            ],
            direction: 'ltr',
            format: '',
            indent: 0,
            type: 'root',
            version: 1,
        },
    } as unknown as SerializedEditorState;
    const [editorState, setEditorState] = useState<SerializedEditorState>(initialValue);
    const [editorHtmlState, setEditorHtmlState] = useState<string>('');

    const [open, setOpen] = useState(false);
    const [date, setDate] = useState<Date | undefined>(undefined);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create knowledge" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="Create knowledge" description="Create new knowledge for the system" />

                <Form
                    {...KnowledgeController.store.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    resetOnSuccess
                    transform={(data) => ({
                        ...data,
                        content: JSON.stringify(editorState),
                        html_content: editorHtmlState,
                        published_date: date ? dayjs(date).format('DD-MM-YYYY') : null,
                    })}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <Card>
                                <CardContent className="space-y-6">
                                    <div className="space-y-6">
                                        <FieldLegend className="sr-only">Knowledge</FieldLegend>
                                        <Field>
                                            <FieldLabel htmlFor="title">Title</FieldLabel>
                                            <Input id="title" name="title" placeholder="Title" />

                                            {errors.title ? <FieldError>{errors.title}</FieldError> : null}
                                        </Field>

                                        <Field>
                                            <FieldLabel htmlFor="content">Content</FieldLabel>
                                            <Editor
                                                editorSerializedState={editorState}
                                                onSerializedChange={(value: SetStateAction<SerializedEditorState<SerializedLexicalNode>>) =>
                                                    setEditorState(value)
                                                }
                                                onChangeHtml={(html) => setEditorHtmlState(html)}
                                            />

                                            {errors.content ? <FieldError>{errors.content}</FieldError> : null}
                                        </Field>

                                        <Field>
                                            <FieldLabel htmlFor="video_url">Video URL</FieldLabel>
                                            <Input id="video_url" name="video_url" placeholder="Video URL" />

                                            {errors.video_url ? <FieldError>{errors.video_url}</FieldError> : null}
                                        </Field>

                                        <div className="grid grid-flow-col gap-2">
                                            <Field>
                                                <FieldLabel htmlFor="published_date">Published Date</FieldLabel>

                                                <Popover open={open} onOpenChange={setOpen}>
                                                    <PopoverTrigger asChild>
                                                        <Button variant="outline" id="published_date" className="justify-between font-normal">
                                                            {date ? date.toLocaleDateString() : 'Select date'}
                                                            <ChevronDown />
                                                        </Button>
                                                    </PopoverTrigger>
                                                    <PopoverContent className="w-auto overflow-hidden p-0" align="start">
                                                        <Calendar
                                                            mode="single"
                                                            selected={date}
                                                            captionLayout="dropdown"
                                                            onSelect={(date) => {
                                                                setDate(date);
                                                                setOpen(false);
                                                            }}
                                                        />
                                                    </PopoverContent>
                                                </Popover>

                                                {errors.published_date ? <FieldError>{errors.published_date}</FieldError> : null}
                                            </Field>

                                            <Field>
                                                <FieldLabel htmlFor="published_time">Published Time</FieldLabel>
                                                <Input
                                                    type="time"
                                                    id="published_time"
                                                    name="published_time"
                                                    step="1"
                                                    className="appearance-none bg-background [&::-webkit-calendar-picker-indicator]:hidden [&::-webkit-calendar-picker-indicator]:appearance-none"
                                                />

                                                {errors.published_time ? <FieldError>{errors.published_time}</FieldError> : null}
                                            </Field>
                                        </div>
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
