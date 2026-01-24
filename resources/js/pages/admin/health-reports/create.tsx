import HealthReportController from '@/actions/App/Http/Controllers/Admin/HealthReportController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/admin/health-reports';
import { BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { FileText, X } from 'lucide-react';
import { useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Health Reports',
        href: index().url,
    },
    {
        title: 'Upload Report',
        href: '',
    },
];

interface User {
    id: number;
    name: string;
    email: string;
}

export default function HealthReportsCreate({ users }: { users: User[] }) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [selectedFiles, setSelectedFiles] = useState<File[]>([]);

    const handleFileChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const files = event.target.files;
        if (files) {
            setSelectedFiles((prev) => [...prev, ...Array.from(files)]);
        }
    };

    const handleRemoveFile = (index: number) => {
        setSelectedFiles((prev) => prev.filter((_, i) => i !== index));
    };

    const handleBrowseClick = () => {
        fileInputRef.current?.click();
    };

    const formatFileSize = (bytes: number) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Upload Health Report" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Upload Health Report" description="Upload health reports for users" />
                </div>

                <Form
                    {...HealthReportController.store.form()}
                    options={{
                        preserveScroll: true,
                        onSuccess: () => {
                            setSelectedFiles([]);
                            if (fileInputRef.current) {
                                fileInputRef.current.value = '';
                            }
                        },
                    }}
                    transform={(data) => ({
                        ...data,
                        files: selectedFiles,
                    })}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <Card className="mt-0">
                                <CardContent className="space-y-6">
                                    <div className="grid gap-2">
                                        <Label htmlFor="user_id">Select User</Label>

                                        <Select name="user_id">
                                            <SelectTrigger id="user_id">
                                                <SelectValue placeholder="Select a user" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {users.map((user) => (
                                                    <SelectItem key={user.id} value={String(user.id)}>
                                                        {user.name} ({user.email})
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>

                                        <InputError message={errors.user_id} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="files">Health Report Files (PDF)</Label>

                                        <input
                                            ref={fileInputRef}
                                            type="file"
                                            name="files[]"
                                            id="files"
                                            multiple
                                            accept=".pdf"
                                            onChange={handleFileChange}
                                            className="hidden"
                                        />

                                        <div className="space-y-4">
                                            <Button type="button" variant="outline" onClick={handleBrowseClick} className="w-full">
                                                <FileText className="mr-2 h-4 w-4" />
                                                Browse Files
                                            </Button>

                                            {selectedFiles.length > 0 && (
                                                <div className="space-y-2">
                                                    <div className="text-sm font-medium">Selected Files ({selectedFiles.length})</div>
                                                    <div className="space-y-2 rounded-md border p-4">
                                                        {selectedFiles.map((file, index) => (
                                                            <div
                                                                key={index}
                                                                className="flex items-center justify-between rounded-md border bg-muted/50 p-2"
                                                            >
                                                                <div className="flex items-center gap-2">
                                                                    <FileText className="h-4 w-4 text-muted-foreground" />
                                                                    <div className="flex flex-col">
                                                                        <span className="text-sm font-medium">{file.name}</span>
                                                                        <span className="text-xs text-muted-foreground">
                                                                            {formatFileSize(file.size)}
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <Button
                                                                    type="button"
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    onClick={() => handleRemoveFile(index)}
                                                                    className="h-8 w-8"
                                                                >
                                                                    <X className="h-4 w-4" />
                                                                </Button>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}
                                        </div>

                                        <InputError message={errors.files} />
                                        {errors['files.0'] && <InputError message={errors['files.0']} />}
                                    </div>
                                </CardContent>
                            </Card>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing || selectedFiles.length === 0}>
                                    Upload Report{selectedFiles.length > 1 ? 's' : ''}
                                </Button>

                                <Button type="button" variant="outline" asChild>
                                    <Link href={index().url}>Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
