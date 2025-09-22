import AppLayout from '@/layouts/app-layout';
import { News, type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/react';

import NewsController from '@/actions/App/Http/Controllers/Admin/NewsController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/admin/news';

import { Editor } from '@/components/editor-00/editor';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { SerializedEditorState, SerializedLexicalNode } from 'lexical';
import { ChevronDown } from 'lucide-react';
import { SetStateAction, useState } from 'react';

import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import dayjs from 'dayjs';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'News',
        href: index().url,
    },
    {
        title: 'Edit news',
        href: '',
    },
];

export default function EditNews({ news }: { news: News }) {
    const initialValue = JSON.parse(news.content) as unknown as SerializedEditorState;
    const [editorState, setEditorState] = useState<SerializedEditorState>(initialValue);

    const [open, setOpen] = useState(false);
    const [date, setDate] = useState<Date | undefined>(news.published_at ? new Date(news.published_at) : undefined);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit news" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <Heading title="Edit news" description="Manage system's news, edit or publish" />

                <Form
                    {...NewsController.update.form(news.id)}
                    options={{
                        preserveScroll: true,
                    }}
                    resetOnSuccess
                    transform={(data) => ({
                        ...data,
                        content: JSON.stringify(editorState),
                        published_date: date ? dayjs(date).format('DD-MM-YYYY') : null,
                    })}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="type">Type</Label>

                                <Select name="type" defaultValue={String(news.type)}>
                                    <SelectTrigger id="type" name="type">
                                        <SelectValue placeholder="Select type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="0">News</SelectItem>
                                        <SelectItem value="1">Promotion</SelectItem>
                                    </SelectContent>
                                </Select>

                                <InputError message={errors.type} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="image">Image</Label>

                                <Input type="file" id="image" name="image" placeholder="Image URL" accept="image/*" />

                                <InputError message={errors.image} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="title">Title</Label>

                                <Input id="title" name="title" placeholder="Title" defaultValue={news.title} />

                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="content">Content</Label>

                                <Editor
                                    editorSerializedState={editorState}
                                    onSerializedChange={(value: SetStateAction<SerializedEditorState<SerializedLexicalNode>>) =>
                                        setEditorState(value)
                                    }
                                />

                                <InputError message={errors.content} />
                            </div>

                            <div className="grid grid-flow-col gap-2">
                                <div className="flex flex-col gap-3">
                                    <Label htmlFor="published_date">Published Date</Label>

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

                                    <InputError message={errors.published_date} />
                                </div>
                                <div className="flex flex-col gap-3">
                                    <Label htmlFor="time-picker">Published Time</Label>

                                    <Input
                                        defaultValue={news.published_time ?? ''}
                                        type="time"
                                        id="time-picker"
                                        name="published_time"
                                        step="1"
                                        className="appearance-none bg-background [&::-webkit-calendar-picker-indicator]:hidden [&::-webkit-calendar-picker-indicator]:appearance-none"
                                    />

                                    <InputError message={errors.published_time} />
                                </div>
                            </div>

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
