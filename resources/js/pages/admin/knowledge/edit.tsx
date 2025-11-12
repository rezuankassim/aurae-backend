import AppLayout from '@/layouts/app-layout';
import { Knowledge, type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/react';

import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index } from '@/routes/admin/knowledge';

import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { SerializedEditorState, SerializedLexicalNode } from 'lexical';
import { ChevronDown, Info } from 'lucide-react';
import { SetStateAction, useState } from 'react';

import KnowledgeController from '@/actions/App/Http/Controllers/Admin/KnowledgeController';
import { Editor } from '@/components/editor-00/editor';
import { Card, CardContent } from '@/components/ui/card';
import { Field, FieldDescription, FieldError, FieldLabel, FieldLegend } from '@/components/ui/field';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import dayjs from 'dayjs';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Knowledge Center Management',
        href: index().url,
    },
    {
        title: 'Edit knowledge',
        href: '#',
    },
];

export default function KnowledgeEdit({ knowledge }: { knowledge: Knowledge }) {
    const initialValue = JSON.parse(knowledge.content) as unknown as SerializedEditorState;
    const [editorState, setEditorState] = useState<SerializedEditorState>(initialValue);
    const [editorHtmlState, setEditorHtmlState] = useState<string>(knowledge.html_content);

    const [open, setOpen] = useState(false);
    const [date, setDate] = useState<Date | undefined>(knowledge.published_at ? new Date(knowledge.published_at) : undefined);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit knowledge" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="Edit knowledge" description="Edit knowledge for the system" />

                <Form
                    {...KnowledgeController.update.form(knowledge.id)}
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
                                            <Input id="title" name="title" placeholder="Title" defaultValue={knowledge.title} />

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
                                            <FieldLabel htmlFor="video_url" className="flex items-center gap-2">
                                                <span>Video URL</span>
                                                <Tooltip>
                                                    <TooltipTrigger>
                                                        <Info className="size-4" />
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        <img src="/example-video-url.png" className="pb-1" />
                                                    </TooltipContent>
                                                </Tooltip>
                                            </FieldLabel>
                                            <Input id="video_url" name="video_url" placeholder="Video URL" defaultValue={knowledge.video_url || ''} />

                                            <FieldDescription>
                                                Use youtube link and copy the link from the clicking "Share" button and click "Embed" and copy the
                                                value from "src"
                                            </FieldDescription>
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
                                                    defaultValue={knowledge.published_time ?? ''}
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
